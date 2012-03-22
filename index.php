<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">

<title>Find similar Twitter users with Saplo API</title>

<link rel="stylesheet" href="styles/bootstrap.min.css" type="text/css" media="screen" />
<link rel="stylesheet" href="styles/style.css" type="text/css" media="screen" />
</head>

<?php require_once 'Service.php'; ?>

<body>

	<div id="page-content" class="container">

		<div class="row">
			<div class="span7 offset2">
				<form class="form-horizontal" action="index.php" method="post">
					<div class="control-group">
						<label class="control-label" for="username">Twitter username</label>
						<div class="controls">
							<div class="input-prepend">
								<span class="add-on">@</span><input id="username" class="span3" value="<?php echo $username; ?>" name="username" size="30" />
								<span class="help-inline"><input type="submit" class="btn btn-success" name="submit" value="Go!" /></span>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>

		<?php if (isset($groups)) { ?>
		<div id="similar-users" class="row">
			<div class="span7 offset2">
				<table class="table">
					<thead>
						<tr>
							<th>Username</th>
							<th>Relevance</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ($groups as $group) { ?>
						<tr>
							<td><?php echo $group['name']; ?></td>
							<td><?php echo $group['relevance']; ?></td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php } ?>

	</div> <!-- container -->

</body>

</html>