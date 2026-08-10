#!/usr/bin/env python3
"""
Face Comparison Script — dlib ResNet128 (via face_recognition library)
Usage:
  python3 face_compare.py --live <base64_jpg> --refs <path1> [<path2> ...]
  OR via stdin JSON: {"live_b64": "...", "ref_paths": ["...", "..."]}

Returns JSON to stdout:
  {"matched": true, "distance": 0.42, "threshold": 0.55, "descriptors": [[...]]}
  OR {"error": "..."}
"""

import sys
import os
import json
import base64
import tempfile
import argparse

def get_encodings(img):
    import face_recognition
    # Try upsample=0 first (best for large/close-up faces in high-res captures)
    locs = face_recognition.face_locations(img, number_of_times_to_upsample=0)
    if not locs:
        # Fallback to upsample=1 for medium distance faces
        locs = face_recognition.face_locations(img, number_of_times_to_upsample=1)
    if not locs:
        # Fallback to upsample=2 for far away faces
        locs = face_recognition.face_locations(img, number_of_times_to_upsample=2)
    if not locs:
        return []
    return face_recognition.face_encodings(img, known_face_locations=locs, num_jitters=1, model="large")

def get_encodings_with_fallback(img):
    from PIL import Image, ImageEnhance
    import numpy as np

    # 1. First run with unmodified original image
    encs = get_encodings(img)
    if encs:
        return encs

    # Convert to PIL Image for enhancements
    try:
        pil_img = Image.fromarray(img)
    except Exception:
        return []

    # 2. Try contrast adjustment fallback
    for con in [1.4, 1.8, 0.7]:
        enhanced = ImageEnhance.Contrast(pil_img).enhance(con)
        encs = get_encodings(np.array(enhanced))
        if encs:
            return encs

    # 3. Try brightness & contrast combined adjustment fallback (good for backlit / dark conditions)
    for bri in [1.3, 1.6, 0.6]:
        enhanced = ImageEnhance.Brightness(pil_img).enhance(bri)
        enhanced = ImageEnhance.Contrast(enhanced).enhance(1.2)
        encs = get_encodings(np.array(enhanced))
        if encs:
            return encs

    # 4. Try sharpness fallback
    for shp in [2.0, 3.0]:
        enhanced = ImageEnhance.Sharpness(pil_img).enhance(shp)
        encs = get_encodings(np.array(enhanced))
        if encs:
            return encs

    # 5. Try rotation fallback (in case the browser/device sent oriented/rotated stream frame)
    for angle in [90, 180, 270]:
        rotated = pil_img.rotate(angle, expand=True)
        encs = get_encodings(np.array(rotated))
        if encs:
            return encs

    return []


def main():
    try:
        import face_recognition
        import numpy as np
    except ImportError as e:
        print(json.dumps({"error": f"Missing library: {e}. Run: pip3 install face_recognition"}))
        sys.exit(1)

    # Read input from stdin JSON or args
    if len(sys.argv) == 1:
        raw = sys.stdin.read().strip()
        if not raw:
            print(json.dumps({"error": "No input provided"}))
            sys.exit(1)
        try:
            data = json.loads(raw)
        except Exception:
            print(json.dumps({"error": "Invalid JSON input"}))
            sys.exit(1)
        live_b64 = data.get("live_b64", "")
        ref_paths = data.get("ref_paths", [])
        extract_only = data.get("extract_only", False)
    else:
        parser = argparse.ArgumentParser()
        parser.add_argument("--live", default="")
        parser.add_argument("--refs", nargs="*", default=[])
        parser.add_argument("--extract-only", action="store_true")
        args = parser.parse_args()
        live_b64 = args.live
        ref_paths = args.refs
        extract_only = args.extract_only

    THRESHOLD = 0.55

    # Load reference descriptors
    ref_encodings = []
    for ref_path in ref_paths:
        if not os.path.exists(ref_path):
            continue
        try:
            img = face_recognition.load_image_file(ref_path)
            encs = get_encodings_with_fallback(img)
            if encs:
                ref_encodings.append(encs[0].tolist())
        except Exception as e:
            sys.stderr.write(f"Warning: could not process {ref_path}: {e}\n")

    # If extract_only mode — just return descriptors for caching
    if extract_only:
        print(json.dumps({
            "descriptors": ref_encodings,
            "count": len(ref_encodings)
        }))
        return

    if not ref_encodings:
        print(json.dumps({
            "matched": False,
            "distance": None,
            "error": "No valid face found in reference photos",
            "descriptors": []
        }))
        return

    # Decode live photo (supports file path or base64 data)
    if not live_b64:
        print(json.dumps({
            "matched": False,
            "distance": None,
            "descriptors": [e for e in ref_encodings],
            "error": "No live photo provided"
        }))
        return

    tmp_path = None
    try:
        if os.path.exists(live_b64):
            live_img = face_recognition.load_image_file(live_b64)
        else:
            # Strip data URI prefix if present
            if "," in live_b64:
                live_b64 = live_b64.split(",", 1)[1]

            # Fix base64 padding if needed
            live_b64_clean = live_b64.strip()
            missing_padding = len(live_b64_clean) % 4
            if missing_padding:
                live_b64_clean += '=' * (4 - missing_padding)

            img_bytes = base64.b64decode(live_b64_clean)
            with tempfile.NamedTemporaryFile(suffix=".jpg", delete=False) as f:
                f.write(img_bytes)
                tmp_path = f.name

            live_img = face_recognition.load_image_file(tmp_path)

        live_encs = get_encodings_with_fallback(live_img)

        if not live_encs:
            print(json.dumps({
                "matched": False,
                "distance": None,
                "descriptors": ref_encodings,
                "match_pct": 0,
                "error": "Wajah tidak terdeteksi pada foto live"
            }))
            return

        live_enc = live_encs[0]
        distances = face_recognition.face_distance(
            [np.array(e) for e in ref_encodings],
            live_enc
        )
        min_dist = float(np.min(distances))
        matched = bool(min_dist <= THRESHOLD)

        print(json.dumps({
            "matched": matched,
            "distance": round(min_dist, 4),
            "threshold": THRESHOLD,
            "descriptors": ref_encodings,
            "match_pct": round((1 - min_dist) * 100, 1)
        }))

    except Exception as e:
        print(json.dumps({"error": str(e), "descriptors": ref_encodings}))
    finally:
        if tmp_path and os.path.exists(tmp_path):
            os.unlink(tmp_path)

if __name__ == "__main__":
    main()
