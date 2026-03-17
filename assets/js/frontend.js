(function () {
	var LOAD_TIMEOUT_MS = 20000;
	var RETRY_DELAY_MS = 800;

	function getMessageNode(wrap) {
		return wrap.querySelector('.s3ds-viewer-message');
	}

	function setViewerState(wrap, state, text) {
		var message = getMessageNode(wrap);
		wrap.classList.remove('is-loading', 'is-error', 'is-ready');
		wrap.classList.add('is-' + state);
		wrap.dataset.viewerState = state;

		if (!message) return;

		if (state === 'error') {
			message.hidden = false;
			message.textContent = text || message.dataset.errorText || message.textContent;
			return;
		}

		if (state === 'loading') {
			message.hidden = false;
			message.textContent = text || message.dataset.loadingText || '';
			return;
		}

		message.hidden = true;
	}

	function getDebugDetails(wrap, reason, extra) {
		var viewer = wrap.querySelector('model-viewer');
		var details = {
			reason: reason,
			errorType: (viewer && viewer.dataset.loadErrorType) || wrap.dataset.loadErrorType || '',
			modelUrl: (viewer && viewer.dataset.modelUrl) || '',
			viewerState: wrap.dataset.viewerState || 'unknown'
		};

		if (!details.modelUrl) {
			details.errorType = 'missing_url';
		} else if (!/\.(glb|gltf)(\?.*)?(#.*)?$/i.test(details.modelUrl)) {
			details.errorType = details.errorType || 'invalid_or_unknown_extension';
		}

		return Object.assign(details, extra || {});
	}

	function debugLog(wrap, reason, extra) {
		if (!window.s3dsFrontend || !window.s3dsFrontend.debug) return;
		console.warn('[Simple 3D Showcase] Viewer state update.', getDebugDetails(wrap, reason, extra));
	}

	function initViewer(wrap) {
		var viewer = wrap.querySelector('model-viewer');
		if (!viewer) return;

		var modelUrl = (viewer.dataset.modelUrl || '').trim();
		var message = getMessageNode(wrap);
		var attempt = 0;
		var loadTimeout = 0;
		var completed = false;

		if (message) {
			message.dataset.errorText = message.textContent;
			message.dataset.loadingText = message.dataset.loadingText || 'Loading 3D model…';
		}

		function clearTimer() {
			if (loadTimeout) {
				clearTimeout(loadTimeout);
				loadTimeout = 0;
			}
		}

		function startTimer() {
			clearTimer();
			loadTimeout = window.setTimeout(function () {
				if (completed) return;
				handleFailure('load_timeout', { attempt: attempt, timeoutMs: LOAD_TIMEOUT_MS });
			}, LOAD_TIMEOUT_MS);
		}

		function handleSuccess(extra) {
			if (completed) return;
			completed = true;
			clearTimer();
			viewer.dataset.loadErrorType = '';
			setViewerState(wrap, 'ready');
			debugLog(wrap, 'model_loaded', extra);
		}

		function retryLoad() {
			attempt += 1;
			setViewerState(wrap, 'loading');
			startTimer();
			viewer.removeAttribute('src');
			window.setTimeout(function () {
				var retryUrl = modelUrl;
				if (attempt > 1) {
					retryUrl += (modelUrl.indexOf('?') === -1 ? '?' : '&') + '_s3ds_retry=' + Date.now();
				}
				viewer.setAttribute('src', retryUrl);
				debugLog(wrap, 'retry_started', { attempt: attempt, src: retryUrl });
			}, RETRY_DELAY_MS);
		}

		function handleFailure(reason, extra) {
			if (completed) return;

			if (attempt < 2) {
				debugLog(wrap, reason + '_retrying', extra);
				retryLoad();
				return;
			}

			completed = true;
			clearTimer();
			wrap.dataset.loadErrorType = wrap.dataset.loadErrorType || reason;
			setViewerState(wrap, 'error', message ? message.dataset.errorText : 'This 3D model is currently unavailable.');
			debugLog(wrap, reason + '_final', extra);
		}

		if (!modelUrl) {
			setViewerState(wrap, 'error', message ? message.dataset.errorText : 'This 3D model is currently unavailable.');
			debugLog(wrap, 'missing_model_url');
			return;
		}

		if (!viewer.getAttribute('src')) {
			viewer.setAttribute('src', modelUrl);
		}

		setViewerState(wrap, 'loading');
		startTimer();
		attempt = 1;

		viewer.addEventListener('load', function () {
			handleSuccess({ attempt: attempt });
		});

		viewer.addEventListener('progress', function (event) {
			if (completed) return;
			var progress = event && event.detail ? Number(event.detail.totalProgress || 0) : 0;
			if (progress > 0 && progress < 1) {
				setViewerState(wrap, 'loading', 'Loading 3D model… ' + Math.round(progress * 100) + '%');
			}
		});

		viewer.addEventListener('error', function (event) {
			handleFailure('model_viewer_error_event', {
				attempt: attempt,
				event: event && event.type
			});
		});
	}

	document.querySelectorAll('.s3ds-viewer').forEach(initViewer);

	document.addEventListener('click', function (event) {
		var wrap = event.target.closest('.s3ds-viewer');
		if (!wrap) return;
		var viewer = wrap.querySelector('model-viewer');
		if (!viewer) return;

		if (event.target.closest('.js-s3ds-reset')) {
			viewer.cameraOrbit = '45deg 75deg auto';
			if (viewer.jumpCameraToGoal) viewer.jumpCameraToGoal();
		}

		if (event.target.closest('.js-s3ds-rotate')) {
			var btn = event.target.closest('.js-s3ds-rotate');
			var rotating = viewer.hasAttribute('auto-rotate');
			if (rotating) {
				viewer.removeAttribute('auto-rotate');
				btn.textContent = btn.getAttribute('data-label-rotate');
			} else {
				viewer.setAttribute('auto-rotate', '');
				btn.textContent = btn.getAttribute('data-label-pause');
			}
		}

		if (event.target.closest('.js-s3ds-fullscreen')) {
			if (!document.fullscreenElement && wrap.requestFullscreen) {
				wrap.requestFullscreen();
			} else if (document.exitFullscreen) {
				document.exitFullscreen();
			}
		}
	});
})();
