<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ReconcileUnavailableCompetingApplications extends Migration
{
    public function up()
    {
        // Backfill applications that were pending before the automatic
        // competing-application closure feature existed.
        $this->db->query("
            UPDATE adoption_application_cats application_cat
            INNER JOIN adoption_applications application ON application.id = application_cat.application_id
            INNER JOIN adoptions adopted ON adopted.animal_id = application_cat.cat_id AND adopted.status = 'Active'
            SET application_cat.status = 'Unavailable'
            WHERE application_cat.status = 'Under Review'
              AND application.adopter_id <> adopted.adopter_id
        ");

        // Older single-cat applications do not have an application_cat row.
        $this->db->query("
            UPDATE adoption_applications application
            INNER JOIN adoptions adopted ON adopted.animal_id = application.cat_id AND adopted.status = 'Active'
            SET application.status = 'Unavailable'
            WHERE application.status = 'Under Review'
              AND application.adopter_id <> adopted.adopter_id
              AND NOT EXISTS (
                  SELECT 1 FROM adoption_application_cats item
                  WHERE item.application_id = application.id
              )
        ");

        // If every cat in a multi-cat application is finished and at least
        // one is unavailable, make the parent application clear as well.
        $this->db->query("
            UPDATE adoption_applications application
            INNER JOIN (
                SELECT application_id
                FROM adoption_application_cats
                GROUP BY application_id
                HAVING SUM(status = 'Under Review') = 0
                   AND SUM(status = 'Approved') = 0
                   AND SUM(status = 'Unavailable') > 0
            ) finished ON finished.application_id = application.id
            SET application.status = 'Unavailable'
            WHERE application.status = 'Under Review'
        ");
    }

    public function down()
    {
        // Preserve reconciled outcomes on rollback; they reflect real adoptions.
    }
}
