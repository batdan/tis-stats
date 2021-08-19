<?php
namespace owid;

use tools\dbSingleton;

/**
 * Source : Our World in Data
 * https://github.com/owid/covid-19-data
 *
 * Collection :
 * https://github.com/owid/covid-19-data/blob/master/public/data/owid-covid-data.csv
 *
 * Permalink :
 * https://github.com/owid/covid-19-data/raw/c5596d49257d5900b958a0c816d207428ae0b016/public/data/owid-covid-data.json
 */
class getOwidData
{
    /**
     * Instance PDO
     * @var object
     */
    private $dbh;

    /**
     * Url stat
     * @var string
     */
    private $url;


    /**
     * Constructor
     */
    public function __construct()
    {
        // Instance PDO
        $this->dbh = dbSingleton::getInstance();

        // Url de la stat
        $this->url = 'https://github.com/owid/covid-19-data/raw/c5596d49257d5900b958a0c816d207428ae0b016/public/data/owid-covid-data.json';

        $this->getData();
    }


    private function getData()
    {
        $table      = 'owid_covid19';
        $countries  = ['FRA', 'GBR', 'SWE', 'MEX'];

        $file = file($this->url);
        $json = $file[0];
        $data = json_decode($json, true);

        // Table principale
        $this->dropTable($table);
        $this->createTable($table);

        $req = "INSERT INTO $table (
            ISO,
            continent,
            location,
            population,
            population_density,
            median_age,
            aged_65_older,
            aged_70_older,
            gdp_per_capita,
            cardiovasc_death_rate,
            diabetes_prevalence,
            female_smokers,
            male_smokers,
            hospital_beds_per_thousand,
            life_expectancy,
            human_development_index,
            activ) VALUES ";

        foreach ($data as $k => $v) {

            $activ = in_array($k, $countries) ? 1 : 0;

            $continent                  = $v['continent'] ?? '';
            $location                   = $v['location'] ?? '';
            $location                   = str_replace("'", "`", $location);

            $population                 = $v['population'] ?? 'NULL';
            $population_density         = $v['population_density'] ?? 'NULL';
            $median_age                 = $v['median_age'] ?? 'NULL';
            $aged_65_older              = $v['aged_65_older'] ?? 'NULL';
            $aged_70_older              = $v['aged_70_older'] ?? 'NULL';
            $gdp_per_capita             = $v['gdp_per_capita'] ?? 'NULL';
            $cardiovasc_death_rate      = $v['cardiovasc_death_rate'] ?? 'NULL';
            $diabetes_prevalence        = $v['diabetes_prevalence'] ?? 'NULL';
            $female_smokers             = $v['female_smokers'] ?? 'NULL';
            $male_smokers               = $v['male_smokers'] ?? 'NULL';
            $hospital_beds_per_thousand = $v['hospital_beds_per_thousand'] ?? 'NULL';
            $life_expectancy            = $v['life_expectancy'] ?? 'NULL';
            $human_development_index    = $v['human_development_index'] ?? 'NULL';

            $req .= "('" . $k . "',";
            $req .= "'" . $continent . "',";
            $req .= "'" . $location . "',";
            $req .= $population . ",";
            $req .= $population_density . ",";
            $req .= $median_age . ",";
            $req .= $aged_65_older . ",";
            $req .= $aged_70_older . ",";
            $req .= $gdp_per_capita . ",";
            $req .= $cardiovasc_death_rate . ",";
            $req .= $diabetes_prevalence . ",";
            $req .= $female_smokers . ",";
            $req .= $male_smokers . ",";
            $req .= $hospital_beds_per_thousand . ",";
            $req .= $life_expectancy . ",";
            $req .= $human_development_index . ",";
            $req .= $activ . "),";
        }

        $req = substr($req, 0, -1);
        $sql = $this->dbh->query($req);

        // Table countries
        foreach ($countries as $country) {

            $tableCountry = $table . '_' . $country;

            $this->dropTable($tableCountry);
            $this->createTableCountry($tableCountry);

            $dataCountry = $data[$country]['data'];

            $req = "INSERT INTO $tableCountry (
                jour,
                total_cases,
                new_cases,
                total_cases_per_million,
                new_cases_per_million,
                hosp_patients,
                hosp_patients_per_million,
                stringency_index,
                excess_mortality,
                icu_patients,
                icu_patients_per_million,
                new_cases_smoothed,
                new_deaths_smoothed,
                new_cases_smoothed_per_million,
                new_deaths_smoothed_per_million,
                total_deaths,
                new_deaths,
                total_deaths_per_million,
                new_deaths_per_million,
                reproduction_rate,
                weekly_icu_admissions,
                weekly_icu_admissions_per_million,
                weekly_hosp_admissions,
                weekly_hosp_admissions_per_million,
                new_tests,
                new_tests_per_thousand,
                tests_units,
                new_tests_smoothed,
                new_tests_smoothed_per_thousand,
                positive_rate,
                tests_per_case,
                total_vaccinations,
                people_vaccinated,
                total_vaccinations_per_hundred,
                people_vaccinated_per_hundred,
                new_vaccinations,
                new_vaccinations_smoothed,
                new_vaccinations_smoothed_per_million,
                people_fully_vaccinated,
                people_fully_vaccinated_per_hundred) VALUES ";

            foreach ($dataCountry as $k => $v) {
                $jour                                   = $v['date'];
                $total_cases                            = $v['total_cases'] ?? 0;
                $new_cases                              = $v['new_cases'] ?? 0;
                $total_cases_per_million                = $v['total_cases_per_million'] ?? 0;
                $new_cases_per_million                  = $v['new_cases_per_million'] ?? 0;
                $hosp_patients                          = $v['hosp_patients'] ?? 0;
                $hosp_patients_per_million              = $v['hosp_patients_per_million'] ?? 0;
                $stringency_index                       = $v['stringency_index'] ?? 0;
                $excess_mortality                       = $v['excess_mortality'] ?? 0;
                $icu_patients                           = $v['icu_patients'] ?? 0;
                $icu_patients_per_million               = $v['icu_patients_per_million'] ?? 0;
                $new_cases_smoothed                     = $v['new_cases_smoothed'] ?? 0;
                $new_deaths_smoothed                    = $v['new_deaths_smoothed'] ?? 0;
                $new_cases_smoothed_per_million         = $v['new_cases_smoothed_per_million'] ?? 0;
                $new_deaths_smoothed_per_million        = $v['new_deaths_smoothed_per_million'] ?? 0;
                $total_deaths                           = $v['total_deaths'] ?? 0;
                $new_deaths                             = $v['new_deaths'] ?? 0;
                $total_deaths_per_million               = $v['total_deaths_per_million'] ?? 0;
                $new_deaths_per_million                 = $v['new_deaths_per_million'] ?? 0;
                $reproduction_rate                      = $v['reproduction_rate'] ?? 0;
                $weekly_icu_admissions                  = $v['weekly_icu_admissions'] ?? 0;
                $weekly_icu_admissions_per_million      = $v['weekly_icu_admissions_per_million'] ?? 0;
                $weekly_hosp_admissions                 = $v['weekly_hosp_admissions'] ?? 0;
                $weekly_hosp_admissions_per_million     = $v['weekly_hosp_admissions_per_million'] ?? 0;
                $new_tests                              = $v['new_tests'] ?? 0;
                $new_tests_per_thousand                 = $v['new_tests_per_thousand'] ?? 0;
                $tests_units                            = $v['tests_units'] ?? 'NULL';
                $new_tests_smoothed                     = $v['new_tests_smoothed'] ?? 0;
                $new_tests_smoothed_per_thousand        = $v['new_tests_smoothed_per_thousand'] ?? 0;
                $positive_rate                          = $v['positive_rate'] ?? 0;
                $tests_per_case                         = $v['tests_per_case'] ?? 0;
                $total_vaccinations                     = $v['total_vaccinations'] ?? 0;
                $people_vaccinated                      = $v['people_vaccinated'] ?? 0;
                $total_vaccinations_per_hundred         = $v['total_vaccinations_per_hundred'] ?? 0;
                $people_vaccinated_per_hundred          = $v['people_vaccinated_per_hundred'] ?? 0;
                $new_vaccinations                       = $v['new_vaccinations'] ?? 0;
                $new_vaccinations_smoothed              = $v['new_vaccinations_smoothed'] ?? 0;
                $new_vaccinations_smoothed_per_million  = $v['new_vaccinations_smoothed_per_million'] ?? 0;
                $people_fully_vaccinated                = $v['people_fully_vaccinated'] ?? 0;
                $people_fully_vaccinated_per_hundred    = $v['people_fully_vaccinated_per_hundred'] ?? 0;

                $req .= "('" . $jour . "',";
                $req .= $total_cases . ",";
                $req .= $new_cases . ",";
                $req .= $total_cases_per_million . ",";
                $req .= $new_cases_per_million . ",";
                $req .= $hosp_patients . ",";
                $req .= $hosp_patients_per_million . ",";
                $req .= $stringency_index . ",";
                $req .= $excess_mortality . ",";
                $req .= $icu_patients . ",";
                $req .= $icu_patients_per_million . ",";
                $req .= $new_cases_smoothed . ",";
                $req .= $new_deaths_smoothed . ",";
                $req .= $new_cases_smoothed_per_million . ",";
                $req .= $new_deaths_smoothed_per_million . ",";
                $req .= $total_deaths . ",";
                $req .= $new_deaths . ",";
                $req .= $total_deaths_per_million . ",";
                $req .= $new_deaths_per_million . ",";
                $req .= $reproduction_rate . ",";
                $req .= $weekly_icu_admissions . ",";
                $req .= $weekly_icu_admissions_per_million . ",";
                $req .= $weekly_hosp_admissions . ",";
                $req .= $weekly_hosp_admissions_per_million . ",";
                $req .= $new_tests . ",";
                $req .= $new_tests_per_thousand . ",";
                $req .= "'" . $tests_units . "',";
                $req .= $new_tests_smoothed . ",";
                $req .= $new_tests_smoothed_per_thousand . ",";
                $req .= $positive_rate . ",";
                $req .= $tests_per_case . ",";
                $req .= $total_vaccinations . ",";
                $req .= $people_vaccinated . ",";
                $req .= $total_vaccinations_per_hundred . ",";
                $req .= $people_vaccinated_per_hundred . ",";
                $req .= $new_vaccinations . ",";
                $req .= $new_vaccinations_smoothed . ",";
                $req .= $new_vaccinations_smoothed_per_million . ",";
                $req .= $people_fully_vaccinated . ",";
                $req .= $people_fully_vaccinated_per_hundred . "),";
            }

            $req = substr($req, 0, -1);
            $sql = $this->dbh->query($req);
        }
    }


    /**
     * Suppression de la table
     * @param  string $table    Nom table
     */
    private function dropTable($table)
    {
        $schema = 'tis_stats';

        $req = "SELECT * FROM information_schema.tables WHERE table_schema = '$schema' AND table_name = '$table'";
        $sql = $this->dbh->query($req);

        if ($sql->rowCount()) {
            $req = "DROP TABLE `$table`";
            $sql = $this->dbh->query($req);
        }
    }

    /**
     * Création de la table globale
     * @param  string $table    Nom table
     */
    private function createTable($table)
    {
        $req = "CREATE TABLE `$table` (
          `id`                              int             NOT NULL,
          `ISO`                             varchar(10)     NULL,
          `continent`                       varchar(20)     NULL,
          `location`                        varchar(40)     NULL,
          `population`                      bigint          NULL,
          `population_density`              decimal(8,3)    NULL,
          `median_age`                      int             NULL,
          `aged_65_older`                   decimal(5,3)    NULL,
          `aged_70_older`                   decimal(5,3)    NULL,
          `gdp_per_capita`                  decimal(10,3)   NULL,
          `cardiovasc_death_rate`           decimal(6,3)    NULL,
          `diabetes_prevalence`             decimal(5,3)    NULL,
          `female_smokers`                  decimal(5,2)    NULL,
          `male_smokers`                    decimal(5,2)    NULL,
          `hospital_beds_per_thousand`      decimal(6,2)    NULL,
          `life_expectancy`                 decimal(4,2)    NULL,
          `human_development_index`         decimal(8,3)    NULL,
          `activ`                           tinyint(1)      NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD PRIMARY KEY (`id`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` MODIFY `id` int NOT NULL AUTO_INCREMENT";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD INDEX(`ISO`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD INDEX(`continent`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD INDEX(`location`)";
        $this->dbh->query($req);
    }

    /**
     * Création de la table du pays
     * @param  string $table    Nom table
     */
    private function createTableCountry($table)
    {
        $req = "CREATE TABLE `$table` (
          `id`                                      int             NOT NULL,
          `jour`                                    date            NOT NULL,

          `total_cases`                             bigint          NULL,
          `new_cases`                               bigint          NULL,

          `total_cases_per_million`                 decimal(10,3)   NULL,
          `new_cases_per_million`                   decimal(10,3)   NULL,

          `hosp_patients`                           int             NULL,
          `hosp_patients_per_million`               decimal(10,3)   NULL,

          `stringency_index`                        decimal(5,3)    NULL,
          `excess_mortality`                        decimal(10,3)   NULL,

          `icu_patients`                            int             NULL,
          `icu_patients_per_million`                decimal(10,3)   NULL,

          `new_cases_smoothed`                      decimal(10,3)   NULL,
          `new_deaths_smoothed`                     decimal(10,3)   NULL,
          `new_cases_smoothed_per_million`          decimal(10,3)   NULL,
          `new_deaths_smoothed_per_million`         decimal(10,3)   NULL,

          `total_deaths`                            int             NULL,
          `new_deaths`                              int             NULL,
          `total_deaths_per_million`                decimal(10,3)   NULL,
          `new_deaths_per_million`                  decimal(10,3)   NULL,
          `reproduction_rate`                       decimal(6,3)    NULL,

          `weekly_icu_admissions`                   decimal(10,3)   NULL,
          `weekly_icu_admissions_per_million`       decimal(10,3)   NULL,
          `weekly_hosp_admissions`                  decimal(10,3)   NULL,
          `weekly_hosp_admissions_per_million`      decimal(10,3)   NULL,

          `new_tests`                               bigint          NULL,
          `new_tests_per_thousand`                  decimal(6,3)    NULL,
          `tests_units`                             varchar(20)     NULL,
          `new_tests_smoothed`                      bigint          NULL,
          `new_tests_smoothed_per_thousand`         decimal(6,3)    NULL,
          `positive_rate`                           decimal(6,3)    NULL,
          `tests_per_case`                          int             NULL,

          `total_vaccinations`                      bigint          NULL,
          `people_vaccinated`                       bigint          NULL,
          `total_vaccinations_per_hundred`          decimal(6,3)    NULL,
          `people_vaccinated_per_hundred`           decimal(6,3)    NULL,
          `new_vaccinations`                        bigint          NULL,
          `new_vaccinations_smoothed`               bigint          NULL,
          `new_vaccinations_smoothed_per_million`   int             NULL,
          `people_fully_vaccinated`                 int             NULL,
          `people_fully_vaccinated_per_hundred`     decimal(6,3)    NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD PRIMARY KEY (`id`)";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` MODIFY `id` int NOT NULL AUTO_INCREMENT";
        $this->dbh->query($req);

        $req = "ALTER TABLE `$table` ADD INDEX(`jour`)";
        $this->dbh->query($req);
    }
}
