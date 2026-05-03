<?php $currentCat = $_GET['conseil'] ?? ''; ?>

<div class="category-toggle">
    <span class="category-label"><i class="fas fa-th-large"></i> Catégorie :</span>

    <a href="conseils.php?conseil=alimentation<?= isset($_GET['age']) ? '&age=' . $_GET['age'] : '' ?>"
        class="<?= $currentCat === 'alimentation' ? 'active' : '' ?>">
        <i class="fas fa-apple-alt"></i> Alimentation
    </a>

    <a href="conseils.php?conseil=sommeil<?= isset($_GET['age']) ? '&age=' . $_GET['age'] : '' ?>"
        class="<?= $currentCat === 'sommeil' ? 'active' : '' ?>">
        <i class="fas fa-moon"></i> Sommeil
    </a>

    <a href="conseils.php?conseil=developpement<?= isset($_GET['age']) ? '&age=' . $_GET['age'] : '' ?>"
        class="<?= $currentCat === 'developpement' ? 'active' : '' ?>">
        <i class="fas fa-child"></i> Développement
    </a>

    <a href="conseils.php?conseil=sante<?= isset($_GET['age']) ? '&age=' . $_GET['age'] : '' ?>"
        class="<?= $currentCat === 'sante' ? 'active' : '' ?>">
        <i class="fas fa-heartbeat"></i> Hygiène/Santé
    </a>
</div>