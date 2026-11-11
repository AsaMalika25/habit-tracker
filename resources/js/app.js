import './bootstrap';
import visionWasmLoaderPath from '@mediapipe/tasks-vision/vision_wasm_internal.js?url';
import visionWasmBinaryPath from '@mediapipe/tasks-vision/vision_wasm_internal.wasm?url';
import { getFaceOverlayPlacement, smoothFaceOverlay } from './face-overlay';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = button.closest('.auth-input')?.querySelector('input');

        button.addEventListener('click', () => {
            const showPassword = input.type === 'password';
            input.type = showPassword ? 'text' : 'password';
            button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', String(showPassword));
        });
    });

    document.querySelectorAll('form[data-submit-once]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = 'true';
            const submitButton = event.submitter ?? form.querySelector('[type="submit"]');
            if (submitButton) {
                submitButton.setAttribute('aria-busy', 'true');
                window.setTimeout(() => { submitButton.disabled = true; });
            }

            const label = submitButton?.querySelector('[data-submit-label]');
            if (label) label.textContent = 'Please wait...';
        });
    });

    const profilePhotoInput = document.querySelector('[data-profile-photo-input]');
    profilePhotoInput?.addEventListener('change', () => {
        const file = profilePhotoInput.files?.[0];
        if (!file) return;

        const preview = document.querySelector('#profilePhotoPreview');
        const fallback = document.querySelector('[data-profile-photo-fallback]');
        const fileName = document.querySelector('[data-profile-photo-name]');
        if (preview) {
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
        }
        if (fallback) fallback.hidden = true;
        if (fileName) fileName.textContent = file.name;
    });

    const changedSettingsForms = new Set();
    const settingsWarning = document.querySelector('[data-settings-warning]');
    document.querySelectorAll('[data-settings-form]').forEach((form) => {
        const markChanged = () => changedSettingsForms.add(form);
        form.addEventListener('input', markChanged);
        form.addEventListener('change', markChanged);
        form.addEventListener('submit', () => changedSettingsForms.delete(form));
    });
    document.querySelectorAll('.settings-tabs a').forEach((link) => {
        link.addEventListener('click', (event) => {
            if (!changedSettingsForms.size) return;
            event.preventDefault();
            settingsWarning.hidden = false;
            settingsWarning.focus();
        });
    });
    window.addEventListener('beforeunload', (event) => {
        if (!changedSettingsForms.size) return;
        event.preventDefault();
        event.returnValue = '';
    });

    document.querySelectorAll('.filters button').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.filters button').forEach((item) => item.classList.remove('active'));
            button.classList.add('active');
            const filter = button.dataset.filter;
            document.querySelectorAll('.habit-group').forEach((group) => {
                group.hidden = filter !== 'all' && group.dataset.category !== filter;
            });
        });
    });

    let activeModal = null;
    const openModal = (selector) => {
        const modal = document.querySelector(selector);
        if (!modal) return;
        activeModal = modal;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        window.setTimeout(() => modal.querySelector('input:not([type="hidden"]), textarea, select, button')?.focus(), 50);
    };
    const closeModal = () => {
        activeModal?.classList.remove('open');
        activeModal?.setAttribute('aria-hidden', 'true');
        activeModal = null;
        document.body.style.overflow = '';
    };
    document.querySelectorAll('[data-open-modal]').forEach((button) => button.addEventListener('click', () => openModal(button.dataset.openModal)));
    document.querySelectorAll('[data-close-modal]').forEach((button) => button.addEventListener('click', closeModal));
    document.addEventListener('keydown', (event) => event.key === 'Escape' && closeModal());

    document.querySelectorAll('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    const streakPopup = document.querySelector('[data-streak-popup]');
    if (streakPopup) {
        const closeStreakPopup = () => {
            if (streakPopup.classList.contains('is-closing')) return;
            streakPopup.classList.add('is-closing');
            window.setTimeout(() => streakPopup.remove(), 230);
        };

        streakPopup.querySelectorAll('[data-close-streak]').forEach((button) => {
            button.addEventListener('click', closeStreakPopup);
        });
        document.addEventListener('keydown', (event) => event.key === 'Escape' && closeStreakPopup());
        streakPopup.querySelector('.streak-celebration__action')?.focus();
        window.setTimeout(closeStreakPopup, 7000);
    }

    const sidebar = document.querySelector('#sidebar');
    const sidebarOverlay = document.querySelector('#sidebarOverlay');
    const menuButton = document.querySelector('#menuButton');
    const setSidebarOpen = (isOpen) => {
        sidebar?.classList.toggle('open', isOpen);
        sidebarOverlay?.classList.toggle('open', isOpen);
        menuButton?.setAttribute('aria-expanded', String(isOpen));
        document.body.classList.toggle('menu-open', isOpen);
    };

    menuButton?.addEventListener('click', () => setSidebarOpen(!sidebar?.classList.contains('open')));
    sidebarOverlay?.addEventListener('click', () => setSidebarOpen(false));
    document.querySelectorAll('.nav a').forEach((link) => link.addEventListener('click', () => setSidebarOpen(false)));
    document.addEventListener('keydown', (event) => event.key === 'Escape' && setSidebarOpen(false));
    window.addEventListener('resize', () => window.innerWidth > 640 && setSidebarOpen(false));

    const journalContent = document.querySelector('#journalContent');
    const journalCount = document.querySelector('#journalCount');
    const journalReadTime = document.querySelector('#journalReadTime');
    const updateJournalStats = () => {
        const content = journalContent?.value ?? '';
        const words = content.trim().split(/\s+/).filter(Boolean).length;
        if (journalCount) journalCount.textContent = `${words} words · ${content.length.toLocaleString()} characters`;
        if (journalReadTime) journalReadTime.textContent = `${Math.max(1, Math.ceil(words / 200))} min read`;
    };
    journalContent?.addEventListener('input', updateJournalStats);
    updateJournalStats();

    document.querySelectorAll('[data-journal-format]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!journalContent) return;
            const start = journalContent.selectionStart;
            const end = journalContent.selectionEnd;
            const selected = journalContent.value.slice(start, end);
            const format = button.dataset.journalFormat;
            const wrappers = {
                bold: ['**', '**'],
                italic: ['_', '_'],
                quote: ['> ', ''],
                link: ['[', '](https://)'],
                list: ['- ', ''],
            };
            const [before, after] = wrappers[format] ?? ['', ''];
            journalContent.setRangeText(`${before}${selected}${after}`, start, end, 'end');
            journalContent.focus();
            updateJournalStats();
        });
    });

    const journalSearchForm = document.querySelector('[data-journal-search-form]');
    const journalSearch = document.querySelector('[data-journal-search]');
    let journalSearchTimer;
    journalSearch?.addEventListener('input', () => {
        window.clearTimeout(journalSearchTimer);
        journalSearchTimer = window.setTimeout(() => journalSearchForm?.requestSubmit(), 450);
    });
    document.querySelectorAll('[data-journal-auto-filter]').forEach((filter) => {
        filter.addEventListener('change', () => journalSearchForm?.requestSubmit());
    });

    const shareImage = async ({ file, url, title }) => {
        if (navigator.share) {
            const data = { title, text: 'Shared from Aura' };
            if (file && navigator.canShare?.({ files: [file] })) {
                data.files = [file];
                await navigator.share(data);
                return 'Shared';
            }
            if (url) {
                data.url = url;
                await navigator.share(data);
                return 'Shared';
            }
        }

        if (url && navigator.clipboard) {
            await navigator.clipboard.writeText(url);
            return 'Link copied';
        }

        if (file) {
            const downloadUrl = URL.createObjectURL(file);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = file.name;
            link.click();
            URL.revokeObjectURL(downloadUrl);
            return 'Downloaded';
        }

        throw new Error('Sharing is not supported on this device.');
    };
    const updateShareButton = (button, label) => {
        const text = button.querySelector('[data-share-label]');
        if (text) text.textContent = label;
    };

    const keepsakeDialog = document.querySelector('[data-keepsake-dialog]');
    if (keepsakeDialog) {
        const image = keepsakeDialog.querySelector('[data-keepsake-image]');
        const title = keepsakeDialog.querySelector('[data-keepsake-title]');
        const date = keepsakeDialog.querySelector('[data-keepsake-date]');
        const shareButton = keepsakeDialog.querySelector('[data-share-keepsake]');
        let trigger;
        let shareFile;

        document.querySelectorAll('[data-open-keepsake]').forEach((button) => {
            button.addEventListener('click', () => {
                trigger = button;
                shareFile = null;
                image.src = button.dataset.keepsakeSrc;
                image.alt = button.querySelector('img').alt;
                title.textContent = button.dataset.keepsakeTitle;
                date.textContent = button.dataset.keepsakeDate;
                updateShareButton(shareButton, 'Share photo');
                keepsakeDialog.showModal();
                const source = button.dataset.keepsakeSrc;
                fetch(source)
                    .then((response) => response.ok ? response.blob() : null)
                    .then((blob) => {
                        if (blob && image.src === source) shareFile = new File([blob], 'aura-keepsake.jpg', { type: blob.type || 'image/jpeg' });
                    })
                    .catch(() => {});
            });
        });
        shareButton.addEventListener('click', async () => {
            shareButton.disabled = true;
            shareButton.setAttribute('aria-busy', 'true');
            try {
                const result = await shareImage({ file: shareFile, url: image.src, title: title.textContent });
                updateShareButton(shareButton, result);
            } catch (error) {
                if (error.name !== 'AbortError') updateShareButton(shareButton, 'Share failed');
            } finally {
                shareButton.disabled = false;
                shareButton.removeAttribute('aria-busy');
            }
        });
        keepsakeDialog.querySelector('[data-close-keepsake]')?.addEventListener('click', () => keepsakeDialog.close());
        keepsakeDialog.addEventListener('click', (event) => {
            if (event.target === keepsakeDialog) keepsakeDialog.close();
        });
        keepsakeDialog.addEventListener('close', () => trigger?.focus());
    }

    const photoBoothDialog = document.querySelector('[data-photobooth-dialog]');
    if (photoBoothDialog) {
        const photoForm = photoBoothDialog.querySelector('[data-photo-form]');
        const video = photoBoothDialog.querySelector('[data-camera-video]');
        const canvas = photoBoothDialog.querySelector('[data-camera-canvas]');
        const cameraViewport = photoBoothDialog.querySelector('.photobooth-viewport');
        const cameraMessage = photoBoothDialog.querySelector('[data-camera-message]');
        const countdown = photoBoothDialog.querySelector('[data-camera-countdown]');
        const collageProgress = photoBoothDialog.querySelector('[data-collage-progress]');
        const backgroundPreview = photoBoothDialog.querySelector('[data-background-preview]');
        const captureButton = photoBoothDialog.querySelector('[data-capture-photo]');
        const retakeButton = photoBoothDialog.querySelector('[data-retake-photo]');
        const shareButton = photoBoothDialog.querySelector('[data-share-photo]');
        const saveButton = photoBoothDialog.querySelector('[data-save-photo]');
        const filterValue = photoBoothDialog.querySelector('[data-photo-filter-value]');
        const activeFilterLabel = photoBoothDialog.querySelector('[data-active-filter-label]');
        const filterButtons = [...photoBoothDialog.querySelectorAll('[data-photo-filter]')];
        const backgroundButtons = [...photoBoothDialog.querySelectorAll('[data-photo-background]')];
        const filterPreviews = [...photoBoothDialog.querySelectorAll('[data-filter-preview]')];
        const cameraOverlays = [...photoBoothDialog.querySelectorAll('[data-camera-overlay]')];
        const faceFilters = new Set(['glasses', 'dog', 'monkey', 'hat']);
        const filters = {
            original: 'none',
            noir: 'grayscale(1) contrast(1.18)',
            sepia: 'sepia(.82) contrast(1.05) saturate(.88)',
            vivid: 'saturate(1.7) contrast(1.12)',
            glow: 'brightness(1.13) saturate(1.18) contrast(.94)',
            glasses: 'none',
            dog: 'none',
            monkey: 'none',
            hat: 'none',
        };
        let activeFilter = 'original';
        let activeBackground = 'none';
        let collageMode = false;
        let collageFrames = [];
        let cameraStream;
        let capturedFile;
        let faceDetectorPromise;
        let faceTrackingFrame;
        let faceTrackingSession = 0;
        let faceTrackingFailed = false;
        let lastFaceScan = 0;
        let missedFaces = 0;
        let trackedFace;

        const syncCameraOverlay = () => {
            cameraOverlays.forEach((overlay) => {
                const visible = overlay.dataset.cameraOverlay === activeFilter && trackedFace;
                overlay.hidden = !visible;
                if (!visible) return;

                let { x, y, width, height } = trackedFace;
                if (collageMode) {
                    const slot = Math.min(collageFrames.length, 3);
                    x = ((slot % 2) + x) / 2;
                    y = (Math.floor(slot / 2) + y) / 2;
                    width /= 2;
                    height /= 2;
                }
                overlay.style.left = `${x * 100}%`;
                overlay.style.top = `${y * 100}%`;
                overlay.style.width = `${width * 100}%`;
                overlay.style.height = `${height * 100}%`;
                overlay.style.transform = `translate(-50%,-50%) rotate(${trackedFace.angle}rad)`;
            });
        };
        const getFaceDetector = () => {
            faceDetectorPromise ??= import('@mediapipe/tasks-vision').then(({ FaceDetector }) => FaceDetector.createFromOptions(
                { wasmLoaderPath: visionWasmLoaderPath, wasmBinaryPath: visionWasmBinaryPath },
                {
                    baseOptions: { modelAssetPath: photoBoothDialog.dataset.faceModel },
                    runningMode: 'VIDEO',
                    minDetectionConfidence: .55,
                },
            )).catch((error) => {
                faceDetectorPromise = null;
                throw error;
            });
            return faceDetectorPromise;
        };
        const trackFace = async (timestamp) => {
            const session = faceTrackingSession;
            if (!cameraStream) return;

            if (faceFilters.has(activeFilter) && video.readyState >= 2 && timestamp - lastFaceScan >= 90 && !faceTrackingFailed) {
                lastFaceScan = timestamp;
                try {
                    const detector = await getFaceDetector();
                    if (!cameraStream || session !== faceTrackingSession) return;
                    const detection = detector.detectForVideo(video, performance.now()).detections[0];
                    const placement = getFaceOverlayPlacement(detection, video.videoWidth, video.videoHeight, activeFilter);
                    if (placement) {
                        trackedFace = smoothFaceOverlay(trackedFace, placement);
                        missedFaces = 0;
                    } else if (++missedFaces > 3) {
                        trackedFace = null;
                    }
                    syncCameraOverlay();
                } catch (error) {
                    faceTrackingFailed = true;
                    trackedFace = null;
                    activeFilter = 'original';
                    syncPhotoBooth();
                    activeFilterLabel.textContent = 'Face tracking unavailable';
                    filterButtons
                        .filter((button) => faceFilters.has(button.dataset.photoFilter))
                        .forEach((button) => { button.disabled = true; });
                    console.warn('Face tracking is unavailable in this browser.', error);
                }
            }
            if (session === faceTrackingSession) faceTrackingFrame = requestAnimationFrame(trackFace);
        };

        const stopCamera = () => {
            faceTrackingSession++;
            cancelAnimationFrame(faceTrackingFrame);
            faceTrackingFrame = null;
            trackedFace = null;
            syncCameraOverlay();
            cameraStream?.getTracks().forEach((track) => track.stop());
            cameraStream = null;
            video.srcObject = null;
            filterPreviews.forEach((preview) => { preview.srcObject = null; });
        };
        const showMessage = (message, isError = false) => {
            cameraMessage.hidden = !message;
            cameraMessage.classList.toggle('error', isError);
            cameraMessage.querySelector('p').textContent = message;
        };
        const drawPhotoOverlay = (context, width, height) => {
            const overlay = cameraOverlays.find((image) => image.dataset.cameraOverlay === activeFilter);
            if (!overlay?.complete || !overlay.naturalWidth || !trackedFace) return;

            const overlayWidth = trackedFace.width * width;
            const overlayHeight = trackedFace.height * height;
            context.save();
            context.translate(trackedFace.x * width, trackedFace.y * height);
            context.rotate(trackedFace.angle);
            context.drawImage(overlay, -overlayWidth / 2, -overlayHeight / 2, overlayWidth, overlayHeight);
            context.restore();
        };
        const paintBackground = (context, width, height) => {
            if (activeBackground === 'none') return;

            context.save();
            if (activeBackground === 'newspaper') {
                const side = width * .065;
                const top = height * .14;
                const bottom = height * .14;
                context.fillStyle = '#e7ddc5';
                context.fillRect(0, 0, width, top);
                context.fillRect(0, height - bottom, width, bottom);
                context.fillRect(0, top, side, height - top - bottom);
                context.fillRect(width - side, top, side, height - top - bottom);
                context.fillStyle = '#201d18';
                context.textAlign = 'center';
                context.font = `700 ${Math.max(18, width * .035)}px Georgia`;
                context.fillText('THE AURA DAILY', width / 2, top * .58);
                context.lineWidth = Math.max(2, width * .003);
                context.beginPath();
                context.moveTo(side, top * .78);
                context.lineTo(width - side, top * .78);
                context.stroke();
                context.lineWidth = Math.max(1, width * .0015);
                for (let row = 1; row <= 3; row++) {
                    const y = height - bottom + (bottom * row / 4);
                    context.beginPath();
                    context.moveTo(side, y);
                    context.lineTo(width - side, y);
                    context.stroke();
                }
            }

            if (activeBackground === 'film') {
                const edge = height * .12;
                context.fillStyle = '#0b0b0c';
                context.fillRect(0, 0, width, edge);
                context.fillRect(0, height - edge, width, edge);
                context.fillStyle = '#ece7da';
                const holeWidth = width * .045;
                const holeHeight = edge * .42;
                for (let x = width * .025; x < width; x += width * .075) {
                    context.fillRect(x, edge * .26, holeWidth, holeHeight);
                    context.fillRect(x, height - edge * .68, holeWidth, holeHeight);
                }
            }

            if (activeBackground === 'notebook') {
                const edge = width * .065;
                context.fillStyle = '#f2ecd9';
                context.fillRect(0, 0, width, height * .1);
                context.fillRect(0, height * .9, width, height * .1);
                context.fillRect(0, height * .1, edge, height * .8);
                context.fillRect(width - edge, height * .1, edge, height * .8);
                context.strokeStyle = '#89a9bd';
                context.lineWidth = Math.max(1, width * .0015);
                for (let y = height * .035; y < height; y += height * .045) {
                    context.beginPath();
                    if (y < height * .1 || y > height * .9) {
                        context.moveTo(0, y);
                        context.lineTo(width, y);
                    } else {
                        context.moveTo(0, y);
                        context.lineTo(edge, y);
                        context.moveTo(width - edge, y);
                        context.lineTo(width, y);
                    }
                    context.stroke();
                }
                context.strokeStyle = '#c66d69';
                context.lineWidth = Math.max(2, width * .0025);
                context.beginPath();
                context.moveTo(edge * .62, 0);
                context.lineTo(edge * .62, height);
                context.stroke();
            }
            context.restore();
        };
        const renderCollage = () => {
            if (!cameraStream || !video.videoWidth) return;

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const context = canvas.getContext('2d');
            if (!context) return;

            const gap = Math.max(8, Math.round(canvas.width * .01));
            const tileWidth = (canvas.width - gap) / 2;
            const tileHeight = (canvas.height - gap) / 2;
            context.fillStyle = '#fff';
            context.fillRect(0, 0, canvas.width, canvas.height);
            for (let index = 0; index < 4; index++) {
                const x = (index % 2) * (tileWidth + gap);
                const y = Math.floor(index / 2) * (tileHeight + gap);
                if (collageFrames[index]) {
                    context.drawImage(collageFrames[index], x, y, tileWidth, tileHeight);
                } else {
                    context.fillStyle = '#151515';
                    context.fillRect(x, y, tileWidth, tileHeight);
                }
            }
            canvas.hidden = false;
            cameraViewport.dataset.collageSlot = String(Math.min(collageFrames.length, 3));
        };
        const syncPhotoBooth = () => {
            const frame = Math.min(collageFrames.length + 1, 4);
            const label = filterButtons
                .find((button) => button.dataset.photoFilter === activeFilter)
                .querySelector(':scope > span:last-child').textContent;
            filterValue.value = collageMode ? 'collage' : activeFilter;
            activeFilterLabel.textContent = collageMode ? `${label} · Frame ${frame} of 4` : `${label} effect`;
            collageProgress.hidden = !collageMode;
            collageProgress.textContent = `Frame ${frame} of 4`;
            video.style.filter = filters[activeFilter];
            syncCameraOverlay();
            cameraViewport.classList.toggle('is-collage', collageMode);
            if (collageMode) {
                renderCollage();
            } else {
                canvas.hidden = true;
                delete cameraViewport.dataset.collageSlot;
            }
            filterButtons.forEach((button) => {
                const selected = button.dataset.photoFilter === activeFilter
                    || (button.dataset.photoFilter === 'collage' && collageMode);
                button.classList.toggle('active', selected);
                button.setAttribute('aria-pressed', String(selected));
            });
            cameraViewport.dataset.photoBackground = activeBackground;
            backgroundButtons.forEach((button) => {
                const selected = button.dataset.photoBackground === activeBackground;
                button.classList.toggle('active', selected);
                button.setAttribute('aria-pressed', String(selected));
            });
        };
        const startCamera = async () => {
            stopCamera();
            collageFrames = [];
            capturedFile = null;
            backgroundPreview.hidden = false;
            canvas.hidden = true;
            video.hidden = false;
            captureButton.hidden = false;
            captureButton.disabled = true;
            retakeButton.hidden = true;
            shareButton.hidden = true;
            updateShareButton(shareButton, 'Share');
            saveButton.hidden = true;
            countdown.hidden = true;
            syncPhotoBooth();
            filterButtons.forEach((button) => { button.disabled = false; });
            backgroundButtons.forEach((button) => { button.disabled = false; });
            photoBoothDialog.classList.remove('is-ready');
            showMessage('Preparing your camera…');

            if (!navigator.mediaDevices?.getUserMedia) {
                showMessage('This browser cannot access a webcam.', true);
                return;
            }

            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } },
                    audio: false,
                });
                video.srcObject = cameraStream;
                await video.play();
                cameraViewport.style.setProperty('--camera-ratio', video.videoWidth + ' / ' + video.videoHeight);
                syncPhotoBooth();
                filterPreviews.forEach((preview) => {
                    preview.srcObject = cameraStream;
                    preview.play().catch(() => {});
                });
                photoBoothDialog.classList.add('is-ready');
                showMessage('');
                captureButton.disabled = false;
                faceTrackingFailed = false;
                lastFaceScan = 0;
                missedFaces = 0;
                faceTrackingFrame = requestAnimationFrame(trackFace);
            } catch {
                showMessage('Allow camera access in your browser to use Photobooth.', true);
            }
        };

        document.querySelectorAll('[data-open-photobooth]').forEach((button) => {
            button.addEventListener('click', () => {
                photoBoothDialog.showModal();
                startCamera();
            });
        });
        photoBoothDialog.querySelectorAll('[data-close-photobooth]').forEach((button) => {
            button.addEventListener('click', () => photoBoothDialog.close());
        });
        photoBoothDialog.addEventListener('close', () => {
            stopCamera();
            photoBoothDialog.classList.remove('is-ready');
        });

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const selectedFilter = button.dataset.photoFilter;
                if (selectedFilter === 'collage') {
                    collageMode = !collageMode;
                    collageFrames = [];
                    activeFilter = 'original';
                } else {
                    activeFilter = selectedFilter;
                }
                trackedFace = null;
                missedFaces = 0;
                syncPhotoBooth();
            });
        });
        backgroundButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeBackground = button.dataset.photoBackground;
                syncPhotoBooth();
            });
        });

        captureButton.addEventListener('click', async () => {
            captureButton.disabled = true;
            filterButtons.forEach((button) => { button.disabled = true; });
            for (let count = 3; count > 0; count--) {
                countdown.textContent = String(count);
                countdown.hidden = false;
                await new Promise((resolve) => window.setTimeout(resolve, 650));
            }
            countdown.hidden = true;

            const context = canvas.getContext('2d');
            if (!context || !video.videoWidth) {
                captureButton.disabled = false;
                filterButtons.forEach((button) => { button.disabled = false; });
                return;
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            if (collageMode) {
                const frame = document.createElement('canvas');
                const frameContext = frame.getContext('2d');
                if (!frameContext) {
                    captureButton.disabled = false;
                    filterButtons.forEach((button) => { button.disabled = false; });
                    return;
                }
                frame.width = canvas.width;
                frame.height = canvas.height;
                frameContext.save();
                frameContext.filter = filters[activeFilter];
                frameContext.translate(frame.width, 0);
                frameContext.scale(-1, 1);
                frameContext.drawImage(video, 0, 0, frame.width, frame.height);
                frameContext.restore();
                drawPhotoOverlay(frameContext, frame.width, frame.height);
                collageFrames.push(frame);
                syncPhotoBooth();

                if (collageFrames.length < 4) {
                    cameraViewport.classList.add('flash');
                    window.setTimeout(() => cameraViewport.classList.remove('flash'), 180);
                    syncPhotoBooth();
                    captureButton.disabled = false;
                    filterButtons.forEach((button) => { button.disabled = false; });
                    return;
                }

                collageProgress.textContent = '4 of 4 complete';
                activeFilterLabel.textContent = 'Collage ready';
            } else {
                context.save();
                context.filter = filters[activeFilter];
                context.translate(canvas.width, 0);
                context.scale(-1, 1);
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                context.restore();
                drawPhotoOverlay(context, canvas.width, canvas.height);
            }
            paintBackground(context, canvas.width, canvas.height);
            cameraViewport.classList.add('flash');
            window.setTimeout(() => cameraViewport.classList.remove('flash'), 180);
            stopCamera();
            cameraOverlays.forEach((overlay) => { overlay.hidden = true; });
            video.hidden = true;
            canvas.hidden = false;
            backgroundPreview.hidden = true;
            captureButton.hidden = true;
            retakeButton.hidden = false;
            shareButton.hidden = false;
            shareButton.disabled = true;
            saveButton.hidden = false;
            filterButtons.forEach((button) => { button.disabled = true; });
            backgroundButtons.forEach((button) => { button.disabled = true; });
            canvas.toBlob((blob) => {
                if (!blob) {
                    updateShareButton(shareButton, 'Share failed');
                    return;
                }
                capturedFile = new File([blob], 'aura-photobooth.jpg', { type: 'image/jpeg' });
                shareButton.disabled = false;
            }, 'image/jpeg', .9);
        });

        retakeButton.addEventListener('click', startCamera);
        shareButton.addEventListener('click', async () => {
            if (!capturedFile) return;
            shareButton.disabled = true;
            shareButton.setAttribute('aria-busy', 'true');
            try {
                const result = await shareImage({ file: capturedFile, title: 'Aura Photo Booth memory' });
                updateShareButton(shareButton, result);
            } catch (error) {
                if (error.name !== 'AbortError') updateShareButton(shareButton, 'Share failed');
            } finally {
                shareButton.disabled = false;
                shareButton.removeAttribute('aria-busy');
            }
        });
        saveButton.addEventListener('click', () => {
            saveButton.disabled = true;
            saveButton.setAttribute('aria-busy', 'true');
            canvas.toBlob(async (blob) => {
                if (!blob) {
                    showMessage('The captured frame could not be prepared.', true);
                    saveButton.disabled = false;
                    saveButton.removeAttribute('aria-busy');
                    return;
                }

                const data = new FormData(photoForm);
                data.set('photo', blob, 'photobooth.jpg');

                try {
                    const response = await fetch(photoForm.action, {
                        method: 'POST',
                        body: data,
                        headers: { Accept: 'application/json' },
                    });
                    if (!response.ok) {
                        const error = await response.json();
                        const message = Object.values(error.errors ?? {}).flat()[0] ?? error.message;
                        throw new Error(message || 'The photo could not be saved.');
                    }
                    window.location.assign(response.url);
                } catch (error) {
                    showMessage(error.message, true);
                    saveButton.disabled = false;
                    saveButton.removeAttribute('aria-busy');
                }
            }, 'image/jpeg', .9);
        });
    }
});
