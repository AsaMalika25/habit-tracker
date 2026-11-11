// Helpers that turn a MediaPipe FaceDetector detection into a placement for the
// photo booth face overlays (glasses, dog, monkey, hat).
//
// Placements are expressed in normalized (0..1) coordinates of the *mirrored*
// preview, because the live video is displayed with `scaleX(-1)` while the
// overlays are positioned (and drawn onto the captured canvas) un-mirrored.
// `x`/`y` are the overlay centre, `width`/`height` its size, `angle` its
// rotation in radians.

// Per-overlay sizing relative to the face box. `offsetY` is expressed in face
// heights from the face centre (negative moves up), `aspect` is the overlay
// image's height / width ratio.
const OVERLAY_PROFILES = {
    glasses: { anchor: 'eyes', scale: 1.15, aspect: .42, offsetY: 0 },
    dog: { anchor: 'face', scale: 1.55, aspect: 1, offsetY: -.08 },
    monkey: { anchor: 'face', scale: 1.5, aspect: 1, offsetY: 0 },
    hat: { anchor: 'face', scale: 1.35, aspect: .8, offsetY: -.78 },
};

const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
const lerp = (from, to, amount) => from + (to - from) * amount;

const lerpAngle = (from, to, amount) => {
    let delta = to - from;
    while (delta > Math.PI) delta -= Math.PI * 2;
    while (delta < -Math.PI) delta += Math.PI * 2;
    return from + delta * amount;
};

export function getFaceOverlayPlacement(detection, videoWidth, videoHeight, filter) {
    const profile = OVERLAY_PROFILES[filter];
    const box = detection?.boundingBox;
    if (!profile || !box || !videoWidth || !videoHeight || !box.width || !box.height) return null;

    // Mirror horizontally so coordinates match the displayed (selfie) preview.
    const faceWidth = box.width / videoWidth;
    const faceHeight = box.height / videoHeight;
    let centerX = 1 - (box.originX + box.width / 2) / videoWidth;
    let centerY = (box.originY + box.height / 2) / videoHeight;

    // Keypoints (normalized): 0 = right eye, 1 = left eye, 2 = nose tip, ...
    const [rightEye, leftEye] = detection.keypoints ?? [];
    let angle = 0;
    if (rightEye && leftEye) {
        const ax = 1 - rightEye.x;
        const bx = 1 - leftEye.x;
        // Normalize to the same pixel scale on both axes before measuring tilt.
        const dx = (bx - ax) * videoWidth;
        const dy = (leftEye.y - rightEye.y) * videoHeight;
        angle = Math.atan2(dy, dx);
        if (angle > Math.PI / 2) angle -= Math.PI;
        if (angle < -Math.PI / 2) angle += Math.PI;

        if (profile.anchor === 'eyes') {
            centerX = (ax + bx) / 2;
            centerY = (rightEye.y + leftEye.y) / 2;
        }
    }

    const width = faceWidth * profile.scale;
    // Height in normalized units, keeping the overlay image's real aspect ratio.
    const height = (width * videoWidth * profile.aspect) / videoHeight;

    // Apply the vertical offset along the face's tilted "up" axis.
    const offset = profile.offsetY * faceHeight;
    const x = centerX - Math.sin(angle) * offset * (videoHeight / videoWidth);
    const y = centerY + Math.cos(angle) * offset;

    return {
        x: clamp(x, -.5, 1.5),
        y: clamp(y, -.5, 1.5),
        width,
        height,
        angle,
    };
}

export function smoothFaceOverlay(previous, next, amount = .45) {
    if (!previous || !next) return next ?? previous ?? null;

    // Snap instead of easing when the face jumps far (e.g. a new person).
    if (Math.hypot(next.x - previous.x, next.y - previous.y) > Math.max(previous.width, .25)) return next;

    return {
        x: lerp(previous.x, next.x, amount),
        y: lerp(previous.y, next.y, amount),
        width: lerp(previous.width, next.width, amount),
        height: lerp(previous.height, next.height, amount),
        angle: lerpAngle(previous.angle, next.angle, amount),
    };
}
