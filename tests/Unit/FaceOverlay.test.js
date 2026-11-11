import assert from 'node:assert/strict';
import test from 'node:test';
import { getFaceOverlayPlacement, smoothFaceOverlay } from '../../resources/js/face-overlay.js';

test('face overlays mirror and follow the detected face', () => {
    const detection = {
        boundingBox: { originX: 100, originY: 60, width: 200, height: 240 },
        keypoints: [
            { x: .18, y: .3 },
            { x: .32, y: .32 },
            { x: .25, y: .45 },
        ],
    };
    const placement = getFaceOverlayPlacement(detection, 800, 450, 'glasses');

    assert.ok(Math.abs(placement.x - .75) < .001);
    assert.ok(placement.angle < 0);
    assert.equal(placement.width, .375);
    assert.equal(getFaceOverlayPlacement(detection, 800, 450, 'original'), null);
    assert.equal(smoothFaceOverlay({ ...placement, x: .5 }, placement, .5).x, .625);
});
