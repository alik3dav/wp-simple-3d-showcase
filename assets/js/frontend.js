(function () {
	function showFriendlyError(wrap) {
		wrap.classList.add('is-error');
		var message = wrap.querySelector('.s3ds-viewer-message');
		if (message) {
			message.hidden = false;
		}
	}

	function getDebugDetails(wrap, reason) {
		var viewer = wrap.querySelector('model-viewer');
		var details = {
			reason: reason,
			errorType: (viewer && viewer.dataset.loadErrorType) || wrap.dataset.loadErrorType || 'load_failure',
			modelUrl: (viewer && viewer.dataset.modelUrl) || ''
		};

		if (!details.modelUrl) {
			details.errorType = 'missing_url';
		} else if (!/\.(glb|gltf)(\?.*)?(#.*)?$/i.test(details.modelUrl)) {
			details.errorType = 'invalid_file';
		}

		return details;
	}

	function debugLog(wrap, reason) {
		if (!window.s3dsFrontend || !window.s3dsFrontend.debug) return;
		var details = getDebugDetails(wrap, reason);
		console.warn('[Simple 3D Showcase] Model unavailable.', details);
	}

	function initViewer(wrap) {
		var viewer = wrap.querySelector('model-viewer');
		if (!viewer) return;

		if (viewer.dataset.loadErrorType) {
			showFriendlyError(wrap);
			debugLog(wrap, 'initial_validation_failed');
			return;
		}

		viewer.addEventListener('error', function () {
			showFriendlyError(wrap);
			debugLog(wrap, 'model_viewer_error_event');
		});

		viewer.addEventListener('load', function () {
			wrap.classList.remove('is-error');
			var message = wrap.querySelector('.s3ds-viewer-message');
			if (message) {
				message.hidden = true;
			}
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
