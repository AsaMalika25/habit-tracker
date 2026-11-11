const layouts = {
    glasses: { scale: 1.5, anchor: 'eyes', artworkY: .458 },
    dog: { scale: 1.7, anchor: 'nose', artworkY: .625 },
    monkey: { scale: 1.6, anchor: 'nose', artworkY: .635 },
    hat: { scale: 1.9, anchor: 'forehead', artworkY: .31 },
};

export const getFaceOverlayPlacement = (detection, videoWidth, videoHeight, filter) => {
    const box = detection?.boundingBox;
    const layout = layouts[filter];
    if (!box || !layout || !videoWidth || !videoHeight) return null;

    const point = (keypoint) => keypoint && ({ x: 1 - keypoint.x, y: keypoint.y });
    const eyes = detection.keypoints?.slice(0, 2).map(point).filter(Boolean).sort((a, b) => a.x - b.x) ?? [];
    const eyeCenter = eyes.length === 2
        ? { x: (eyes[0].x + eyes[1].x) / 2, y: (eyes[0].y + eyes[1].y) / 2 }
        : null;
    const boxCenter = {
        x: 1 - ((box.originX + box.width / 2) / videoWidth),
        y: (box.originY + box.height / 2) / videoHeight,
    };
    const anchors = {
        eyes: eyeCenter ?? { x: boxCenter.x, y: box.originY / videoHeight + (box.height / videoHeight * .38) },
        nose: point(detection.keypoints?.[2]) ?? boxCenter,
        forehead: { x: boxCenter.x, y: (box.originY + box.height * .12) / videoHeight },
    };
    const width = box.width * layout.scale;
    const height = width * .562;
    const anchor = anchors[layout.anchor];
    const angle = eyes.length === 2
        ? Math.atan2((eyes[1].y - eyes[0].y) * videoHeight, (eyes[1].x - eyes[0].x) * videoWidth)
        : 0;

    return {
        x: anchor.x,
        y: anchor.y + ((.5 - layout.artworkY) * height / videoHeight),
        width: width / videoWidth,
        height: height / videoHeight,
        angle,
    };
};

export const smoothFaceOverlay = (previous, next, amount = .3) => {
    if (!previous) return next;
    return Object.fromEntries(
        Object.keys(next).map((key) => [key, previous[key] + (next[key] - previous[key]) * amount]),
    );
};
