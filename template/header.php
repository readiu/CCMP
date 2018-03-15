<?php
require_once 'apps/ww2.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php if ($this->title != '') { echo htmlspecialchars($this->title) . ' | '; } ?>Central College</title>
<link rel="stylesheet"
	href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css"
	integrity="sha256-916EbMg70RQy9LHiGkXzG8hSg9EdNy97GazNG/aiY1w=" crossorigin="anonymous" />
<link rel="stylesheet" href="/_static/css/ww2.css" type="text/css">
<link
	href='https://fonts.googleapis.com/css?family=Open+Sans:400,400italic,700,700italic&subset=latin,latin-ext'
	rel='stylesheet' type='text/css'>
<link rel="shortcut icon" href="https://d1lqhpmxg10s5j.cloudfront.net/favicon.ico" />
<?=$this->headAppend?>
</head>
<body>
	<div class="container" id="container">
		<nav class="navbar navbar-default" id="nav">
			<div class="container-fluid">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
						data-target="#navbar-collapse" aria-expanded="false">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<span class="navbar-brand">
						<a href="https://www.central.edu">
							<img alt="Central College logo"
								src="https://d1lqhpmxg10s5j.cloudfront.net/images/2015/centralCollegeLogo.png" />
						</a>
						<?php echo htmlspecialchars($this->title); ?>
					</span>
				</div>
				<div class="collapse navbar-collapse" id="navbar-collapse">
					<ul class="nav navbar-nav navbar-right">
						<?php
						if (!empty($apps)) {
						?>
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
									aria-haspopup="true" aria-expanded="false">
									<span class="glyphicon glyphicon-th" aria-hidden="true"></span>
									Apps
									<span class="caret"></span>
								</a>
								<ul class="dropdown-menu" id="menu-apps">
									<?php
									foreach ($apps as $app) {
										echo '<li class="disabled"><a><strong><em style="color:#e51937;">' . htmlspecialchars($app['name']) .
											'</em></strong></a></li>';
										if (!empty($app['items'])) {
											foreach ($app['items'] as $menuItem) {
												echo '<li>' . '<a href="' .
														 htmlspecialchars($menuItem['path']) . '" ><span style="color:#717073;">' .
														 htmlspecialchars($menuItem['name']) . '</span></a>' .
														 '</li>';
											}
										}
									}
									?>
								</ul>
							</li>
						<?php
						}
						if (!empty($_SESSION["authenticated"])) {
						?>
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
									aria-haspopup="true" aria-expanded="false">
									<span class="glyphicon glyphicon-user" aria-hidden="true"></span>
									<?= $_SESSION['username'] ?>
									<span class="caret"></span>
								</a>
								<ul class="dropdown-menu">
									<li>
										<a href="/_static/login/logout.php">
											Logout
											<span class="glyphicon glyphicon-log-out" aria-hidden="true"></span>
										</a>
									</li>
								</ul>
							</li>
						<?php
						}
						?>
					</ul>
				</div>
			</div>
		</nav>
		<div id="content">
	