import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-submit-once]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = 'true';
            const submitButton = event.submitter ?? form.querySelector('[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.setAttribute('aria-busy', 'true');
            }

            const label = submitButton?.querySelector('[data-submit-label]');
            if (label) label.textContent = 'Please wait...';
        });
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

    document.querySelectorAll('.moods button').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.moods button').forEach((item) => item.classList.remove('active'));
            button.classList.add('active');
        });
    });

    const reflection = document.querySelector('#reflection');
    const wordCount = document.querySelector('#wordCount');
    const countWords = () => {
        const total = reflection?.value.trim().split(/\s+/).filter(Boolean).length ?? 0;
        if (wordCount) wordCount.textContent = `${total} words`;
    };
    reflection?.addEventListener('input', countWords);
    countWords();

    const saveButton = document.querySelector('#saveReflection');
    saveButton?.addEventListener('click', () => {
        const label = saveButton.querySelector('span');
        const icon = saveButton.querySelector('i');
        if (label) label.textContent = 'Saved to Vault';
        if (icon) icon.textContent = 'done';
        window.setTimeout(() => {
            if (label) label.textContent = 'Save Reflection';
            if (icon) icon.textContent = 'bookmark_check';
        }, 2200);
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

    const photoBoothDialog = document.querySelector('[data-photobooth-dialog]');
    if (photoBoothDialog) {
        const photoForm = photoBoothDialog.querySelector('[data-photo-form]');
        const video = photoBoothDialog.querySelector('[data-camera-video]');
        const canvas = photoBoothDialog.querySelector('[data-camera-canvas]');
        const cameraViewport = photoBoothDialog.querySelector('.photobooth-viewport');
        const cameraMessage = photoBoothDialog.querySelector('[data-camera-message]');
        const countdown = photoBoothDialog.querySelector('[data-camera-countdown]');
        const collageProgress = photoBoothDialog.querySelector('[data-collage-progress]');
        const captureButton = photoBoothDialog.querySelector('[data-capture-photo]');
        const retakeButton = photoBoothDialog.querySelector('[data-retake-photo]');
        const saveButton = photoBoothDialog.querySelector('[data-save-photo]');
        const filterValue = photoBoothDialog.querySelector('[data-photo-filter-value]');
        const activeFilterLabel = photoBoothDialog.querySelector('[data-active-filter-label]');
        const filterButtons = [...photoBoothDialog.querySelectorAll('[data-photo-filter]')];
        const filterPreviews = [...photoBoothDialog.querySelectorAll('[data-filter-preview]')];
        const filters = {
            original: 'none',
            noir: 'grayscale(1) contrast(1.18)',
            sepia: 'sepia(.82) contrast(1.05) saturate(.88)',
            vivid: 'saturate(1.7) contrast(1.12)',
            glow: 'brightness(1.13) saturate(1.18) contrast(.94)',
        };
        let activeFilter = 'original';
        let collageMode = false;
        let collageFrames = [];
        let cameraStream;

        const stopCamera = () => {
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
        };
        const startCamera = async () => {
            stopCamera();
            collageFrames = [];
            canvas.hidden = true;
            video.hidden = false;
            captureButton.hidden = false;
            captureButton.disabled = true;
            retakeButton.hidden = true;
            saveButton.hidden = true;
            countdown.hidden = true;
            syncPhotoBooth();
            filterButtons.forEach((button) => { button.disabled = false; });
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
                frameContext.filter = filters[activeFilter];
                frameContext.translate(frame.width, 0);
                frameContext.scale(-1, 1);
                frameContext.drawImage(video, 0, 0, frame.width, frame.height);
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
            }
            cameraViewport.classList.add('flash');
            window.setTimeout(() => cameraViewport.classList.remove('flash'), 180);
            stopCamera();
            video.hidden = true;
            canvas.hidden = false;
            captureButton.hidden = true;
            retakeButton.hidden = false;
            saveButton.hidden = false;
            filterButtons.forEach((button) => { button.disabled = true; });
        });

        retakeButton.addEventListener('click', startCamera);
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
