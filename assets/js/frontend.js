(function () {
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
