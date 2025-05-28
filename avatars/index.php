<!DOCTYPE html>
<html>
	<head>

		<title>AvatarMaker</title>

		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
		<meta name="author" content="InochiTeam (https://inochiteam.com)">

		<!-- Styles -->
		<link rel="stylesheet" href="app/avatarmaker.css" type="text/css">
    <link rel="icon" href="app/favicon.png">

		<!-- Scripts and libraries -->
		<script src="app/jquery-3.6.1.min.js"></script>
		<script src="app/avatarmaker.js"></script>

		<script>
			$( document ).ready(function() {
				// Initialize the avatarMaker
				$( "#avmContainer" ).avatarMaker('avatarmaker.php');
			});
		</script>

	</head>
	<body>
		<svg style="display:none">
			<symbol id="icon-inochiteam" viewBox="0 0 48 48">
				<path d="M26.9,21.8h-5.6h-4c-0.1,0-0.4,0-0.4,0s0-0.1,0-0.4v-2.4c-2.1,1.3-4.4,2.7-7,3.6c-0.4,0.1-0.6,0.4-0.7,0.4s-0.4-0.4-0.7-1c-0.4-1-0.7-1.7-1.6-2.4c3.4-1.3,6-2.7,8.1-4c2.1-1.3,3.9-3,5.3-4.7c0.7-1,1.3-1.7,1.7-2.7c1.6,0.1,2.9,0.6,3.9,0.7C26.4,9,26.5,9,26.5,9.4c0,0.1-0.1,0.4-0.4,0.6l-0.1,0.1c2.1,2.3,4.4,4.4,7.1,5.8c2.3,1,5,2.1,8,3.3c-0.6,0.6-1,1.6-1.3,2.7c-0.1,0.6-0.4,1-0.6,1c-0.1,0-0.4,0-1-0.1c-2.7-1.1-5.1-2.3-7.1-3.6v2.4c0,0.1,0,0.4,0,0.4s-0.1,0-0.4,0L26.9,21.8zM22.4,37.6c0,0.1,0,0.4,0,0.4s-0.1,0-0.4,0h-2.7c-0.1,0-0.4,0-0.4,0s0-0.1,0-0.4v-1.3h-4.7v2.1c0,0.1,0,0.4,0,0.4s-0.1,0-0.4,0h-2.4c-0.1,0-0.4,0-0.4,0s0-0.1,0-0.4v-6.3v-3v-4.1c0-0.1,0-0.4,0-0.4s0.1,0,0.4,0h3.4h3.7h3.4c0.1,0,0.4,0,0.4,0s0,0.1,0,0.4v3.6v3.3L22.4,37.6z M18.8,27.9h-4.6v5.3h4.6V27.9z M17.8,18.5h3.6h5.6h3.6c-2.4-1.7-4.7-3.9-6.4-5.8C22.4,14.8,20.2,16.8,17.8,18.5zM30.1,34.8c0.6,0,1.6,0.1,2.3,0.1c0.6,0,1,0,1.1-0.1c0.1-0.1,0.1-0.6,0.1-1v-6h-5.1v9.8v4c0,0.1,0,0.4,0,0.4s-0.1,0-0.4,0h-3c-0.1,0-0.4,0-0.4,0s0-0.1,0-0.4v-4v-8.8v-4c0-0.1,0-0.4,0-0.4s0.1,0,0.4,0h3.6h4.4h3.9c0.1,0,0.4,0,0.4,0s0,0.1,0,0.4v3.4v2.1v4.4c0,1.6-0.4,2.4-1,3c-0.7,0.6-2.3,1-4.1,1c-0.6,0-1,0-1-0.1c-0.1-0.1-0.1-0.4-0.4-1C30.9,36.5,30.5,35.5,30.1,34.8z"/>
			</symbol>
			<symbol id="icon-random" viewBox="0 0 48 48">
				<path d="M17.8,14.6c-1.1,1.6-2.3,4.1-3.7,7.3c-0.4-0.8-0.7-1.5-1-1.9c-0.3-0.5-0.6-1.1-1.1-1.7c-0.5-0.6-0.9-1.1-1.4-1.5	c-0.5-0.4-1-0.7-1.7-0.9c-0.7-0.3-1.4-0.4-2.2-0.4h-6c-0.3,0-0.5-0.1-0.6-0.2S0,14.8,0,14.6V9.4C0,9.2,0.1,9,0.2,8.8s0.4-0.2,0.6-0.2h6C11.3,8.6,15,10.6,17.8,14.6zM48,36c0,0.3-0.1,0.5-0.2,0.6l-8.6,8.6c-0.2,0.2-0.4,0.2-0.6,0.2c-0.2,0-0.4-0.1-0.6-0.3s-0.3-0.4-0.3-0.6v-5.1c-0.6,0-1.3,0-2.3,0s-1.7,0-2.2,0s-1.2,0-2,0c-0.8,0-1.4-0.1-1.9-0.1c-0.5-0.1-1-0.2-1.7-0.3c-0.7-0.1-1.2-0.3-1.7-0.5c-0.4-0.2-1-0.5-1.6-0.8c-0.6-0.3-1.1-0.7-1.6-1.1c-0.5-0.4-1-0.9-1.5-1.4s-1-1.2-1.5-1.9c1.1-1.7,2.3-4.1,3.6-7.3c0.4,0.8,0.7,1.5,1,1.9c0.3,0.5,0.6,1.1,1.1,1.7c0.5,0.6,0.9,1.1,1.4,1.5c0.5,0.4,1,0.7,1.7,0.9c0.7,0.3,1.4,0.4,2.2,0.4h6.9v-5.1c0-0.3,0.1-0.5,0.2-0.6s0.4-0.2,0.6-0.2c0.2,0,0.4,0.1,0.6,0.3l8.5,8.5C47.9,35.5,48,35.8,48,36zM48,12c0,0.3-0.1,0.5-0.2,0.6l-8.6,8.6c-0.2,0.2-0.4,0.2-0.6,0.2c-0.2,0-0.4-0.1-0.6-0.3c-0.2-0.2-0.3-0.4-0.3-0.6v-5.1h-6.9c-0.9,0-1.6,0.1-2.3,0.4c-0.7,0.3-1.3,0.7-1.8,1.2c-0.5,0.5-1,1.1-1.4,1.6s-0.8,1.3-1.2,2.1c-0.6,1.1-1.3,2.6-2.1,4.6c-0.5,1.2-1,2.2-1.3,3c-0.4,0.8-0.8,1.7-1.4,2.8c-0.6,1.1-1.2,2-1.7,2.7c-0.5,0.7-1.2,1.5-2,2.2s-1.6,1.4-2.4,1.8c-0.8,0.5-1.8,0.8-2.9,1.1s-2.2,0.4-3.4,0.4h-6c-0.3,0-0.5-0.1-0.6-0.2S0,38.8,0,38.6v-5.1c0-0.3,0.1-0.5,0.2-0.6s0.4-0.2,0.6-0.2h6c0.9,0,1.6-0.1,2.3-0.4c0.7-0.3,1.3-0.7,1.8-1.2c0.5-0.5,1-1.1,1.4-1.6s0.8-1.3,1.2-2.1c0.6-1.1,1.3-2.6,2.1-4.6c0.5-1.2,1-2.2,1.3-3s0.8-1.7,1.4-2.8s1.2-2,1.7-2.7c0.5-0.7,1.2-1.5,2-2.2c0.8-0.8,1.6-1.4,2.4-1.8c0.8-0.5,1.8-0.8,2.9-1.1c1.1-0.3,2.2-0.4,3.4-0.4h6.9V3.4c0-0.3,0.1-0.5,0.2-0.6s0.4-0.2,0.6-0.2c0.2,0,0.4,0.1,0.6,0.3l8.5,8.5C47.9,11.5,48,11.8,48,12z"/>
			</symbol>
			<symbol id="icon-download" viewBox="0 0 48 48">
				<path d="M36.9,40.6c0-0.5-0.2-0.9-0.5-1.3s-0.8-0.5-1.3-0.5s-0.9,0.2-1.3,0.5s-0.5,0.8-0.5,1.3c0,0.5,0.2,0.9,0.5,1.3c0.4,0.4,0.8,0.5,1.3,0.5s0.9-0.2,1.3-0.5C36.7,41.5,36.9,41.1,36.9,40.6z M44.3,40.6c0-0.5-0.2-0.9-0.5-1.3s-0.8-0.5-1.3-0.5c-0.5,0-0.9,0.2-1.3,0.5c-0.4,0.4-0.5,0.8-0.5,1.3c0,0.5,0.2,0.9,0.5,1.3c0.4,0.4,0.8,0.5,1.3,0.5c0.5,0,0.9-0.2,1.3-0.5C44.1,41.5,44.3,41.1,44.3,40.6z M48,34.2v9.2c0,0.8-0.3,1.4-0.8,2s-1.2,0.8-2,0.8H2.8c-0.8,0-1.4-0.3-2-0.8S0,44.2,0,43.4v-9.2c0-0.8,0.3-1.4,0.8-2s1.2-0.8,2-0.8h13.4l3.9,3.9c1.1,1.1,2.4,1.6,3.9,1.6s2.8-0.5,3.9-1.6l3.9-3.9h13.4c0.8,0,1.4,0.3,2,0.8S48,33.4,48,34.2z M38.6,17.7c0.3,0.8,0.2,1.5-0.4,2L25.3,32.7C25,33,24.5,33.2,24,33.2s-1-0.2-1.3-0.5L9.8,19.8c-0.6-0.6-0.7-1.2-0.4-2c0.3-0.8,0.9-1.1,1.7-1.1h7.4V3.7c0-0.5,0.2-0.9,0.5-1.3s0.8-0.5,1.3-0.5h7.4c0.5,0,0.9,0.2,1.3,0.5s0.5,0.8,0.5,1.3v12.9h7.4C37.7,16.6,38.3,17,38.6,17.7z"/>
			</symbol>
			<symbol id="icon-arrow" viewBox="0 0 48 48">
				<path d="M17.2,32.9l9.2-9.2l-9.2-9.2l2.8-2.8l12,12l-12,12L17.2,32.9z"/>
			</symbol>
			<symbol id="icon-picker" viewBox="0 0 48 48">
				<path d="M41.4,11.3l-4.7-4.7c-0.8-0.8-2-0.8-2.8,0l-6.3,6.3L23.8,9L21,11.8l2.8,2.8L6,32.5V42h9.5l17.8-17.8l2.8,2.8l2.8-2.8l-3.8-3.8l6.3-6.3C42.2,13.3,42.2,12,41.4,11.3z M13.8,38L10,34.2L26.1,18l3.8,3.8L13.8,38z"/>
			</symbol>
		</svg>

		<!-- Avatar Maker Start -->
		<div class="avatarMaker">

			<!-- error Overlay -->
			<div class="panel-error">
				<div class="error-content">
					<h2>Ops... Something went wrong</h2>
					<p>An error has occurred that prevents the app from loading.</p>
					<p><a href=".">Reloading the page</a> may fix the issue.</p>
					<code class="error-details">No details available</code>
				</div>
			</div>

			<!-- Loading Overlay -->
			<div class="panel-loading">
				<div class="loading-content">
					AvatarMaker - Please wait...
					<div class="loading-progress"><div class="progress-bar"></div></div>
				</div>
			</div>

			<!-- Download Overlay -->
			<div class="panel-download">
				<div class="panel-download-content">
					<h3 avm-local="dw_preparing"></h3>
					<span avm-local="dw_wait"></span>
					<div class="download-progress"><div class="progress-bar"></div></div>
				</div>
			</div>

			<!-- Sidebar Start -->
			<aside class="panel-side">
				<header class="side-header" id="app_brand"></header>

				<!-- Preview Box -->
				<div class="side-preview">
					<canvas height="400" width="400" id="previewBox"></canvas>
				</div>

				<!-- Sidebar Menu -->
				<ul class="side-menu">
					<li id="btn_random"><svg><use xlink:href="#icon-random" /></svg> <span avm-local="sm_random"></span></li>
					<li id="btn_download"><svg><use xlink:href="#icon-download" /></svg> <span avm-local="sm_download"></span></li>
				</ul>

			<!--	<div class="side-branding"><span avm-local="sm_credits"></span> <a href="https://inochiteam.com" target="_blank">InochiTeam</a></div> -->
			</aside>
			<!-- Sidebar End -->


			<div class="panel-main">

				<!-- Layers Menu Sart -->
				<header class="layers-menu">
					<ul>
						<li avm-layerId="head" avm-local="tm_head" class="active"></li>
						<li avm-layerId="ears" avm-local="tm_ears"></li>
						<li avm-layerId="eyes" avm-local="tm_eyes"></li>
						<li avm-layerId="eyebrows" avm-local="tm_eyebrows"></li>
						<li avm-layerId="nose" avm-local="tm_nose"></li>
						<li avm-layerId="mouth" avm-local="tm_mouth"></li>
						<li avm-layerId="hair" avm-local="tm_hair"></li>
						<li avm-layerId="objects" avm-local="tm_objects"></li>
						<li avm-layerId="background" avm-local="tm_background"></li>
					</ul>
				</header>
				<!-- Layers Menu End -->

				<!-- Layers Tabs -->
				<ul class="layers-tabs"></ul>

				<div class="palettes-tabs">

					<ul class="tabs-content"></ul>

					<ul class="tabs-pagination"></ul>

					<div class="tabs-arrows">
						<div class="tabs-arrow arrow-top"><svg><use xlink:href="#icon-arrow" /></svg></div>
						<div class="tabs-arrow arrow-bottom"><svg><use xlink:href="#icon-arrow" /></svg></div>
					</div>

					<div class="palettes-picker">

							<div class="picker-button">
								<svg><use xlink:href="#icon-picker" /></svg>
							</div>

							<div class="picker-modal">
								<input type='hidden' id="picker-place" />
							</div>

					</div>



				</div>


			</div>

		</div>
		<!-- Avatar Maker End -->

	</body>
</html>
