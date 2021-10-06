<?php
/**
 * Récupéraiton et traiement du jeu de données demo_pjan
 */
class demoPjan
{
    public function __construct()
    {
        $test = new eurostat\downloadFile();
        $file = $test->getDataset('demo_pjan');
    }
}
