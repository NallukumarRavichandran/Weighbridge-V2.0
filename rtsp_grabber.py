import sys
import os
import cv2

# Usage: python rtsp_grabber.py <rtsp_url> <output_image_path>
if len(sys.argv) < 3:
    print("ERROR: Missing arguments. Usage: python rtsp_grabber.py <rtsp_url> <output_image_path>")
    sys.exit(1)

rtsp_url = sys.argv[1]
out_path = sys.argv[2]

try:
    # Enforce TCP transport for pristine, artifact-free 1080P frame acquisition
    os.environ["OPENCV_FFMPEG_CAPTURE_OPTIONS"] = "rtsp_transport;tcp"
    
    cap = cv2.VideoCapture(rtsp_url, cv2.CAP_FFMPEG)
    if not cap.isOpened():
        print("ERROR: Cannot open RTSP stream")
        sys.exit(1)

    # Grab 3 frames to bypass initial H.264 P-frame / buffer decode artifacts
    ret = False
    frame = None
    for _ in range(3):
        ret, frame = cap.read()
    cap.release()

    if ret and frame is not None and frame.size > 0:
        cv2.imwrite(out_path, frame, [cv2.IMWRITE_JPEG_QUALITY, 98])
        print("SUCCESS")
        sys.exit(0)
    else:
        print("ERROR: Empty frame received")
        sys.exit(1)
except Exception as e:
    print(f"ERROR: {str(e)}")
    sys.exit(1)
