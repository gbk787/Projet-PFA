<div class="age-bloc">
    <h2>Choisissez l'âge de votre bébé</h2>

    <div class="ages">
        <?php $cat = htmlspecialchars($_GET['conseil'] ?? ''); ?>
        <a href="?age=1&conseil=<?= $cat ?>#res" class="age"><i class="fa-solid fa-baby"></i> 0-3 mois</a>
        <a href="?age=2&conseil=<?= $cat ?>#res" class="age"><i class="fa-solid fa-baby-carriage"></i> 4-6 mois</a>
        <a href="?age=3&conseil=<?= $cat ?>#res" class="age"><i class="fa-solid fa-face-smile"></i> 7-9 mois</a>
        <a href="?age=4&conseil=<?= $cat ?>#res" class="age"><i class="fa-solid fa-child"></i> 10-12 mois</a>
        <a href="?age=5&conseil=<?= $cat ?>#res" class="age"><i class="fa-solid fa-person"></i> 1-2 ans</a>
    </div>
</div>