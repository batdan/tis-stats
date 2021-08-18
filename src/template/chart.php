<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <title><?php echo (!empty($title)) ? $title : ''; ?></title>

        <link rel="icon" href="https://www.lachainehumaine.com/wp-content/uploads/2021/07/cropped-logo-1-60x60.png" sizes="32x32" />
        <link rel="icon" href="https://www.lachainehumaine.com/wp-content/uploads/2021/07/cropped-logo-1-300x300.png" sizes="192x192" />
        <link rel="apple-touch-icon" href="https://www.lachainehumaine.com/wp-content/uploads/2021/07/cropped-logo-1-300x300.png" />

        <link href="/css/styles.css" rel="stylesheet" type="text/css">
    </head>

    <body>
        <figure class="highcharts-figure">
            <div id="container"></div>
        </figure>

        <script type="text/javascript" src="//code.highcharts.com/highcharts.js"></script>
        <script type="text/javascript" src="//code.highcharts.com/modules/exporting.js"></script>
        <script type="text/javascript">
            <?php echo $js; ?>
        </script>
    </body>
</html>
