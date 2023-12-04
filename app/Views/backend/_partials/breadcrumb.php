<div class="page-header">
	<h4 class="page-title"><?= $title ? $title : '' ?></h4>
	<?php if ($title) : ?>
		<ul class="breadcrumbs">
			<li class="nav-home">
				<a href="<?= site_url('sas') ?>">
					<i class="flaticon-home"></i>
				</a>
			</li>
			<li class="separator">
				<i class="flaticon-right-arrow"></i>
			</li>
			<li class="nav-item">
				<a><?= $title ?></a>
			</li>
		</ul>
	<?php endif ?>
</div>