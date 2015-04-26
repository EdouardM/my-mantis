SELECT 	bug_t.id AS "ticket #",
		user_t.realname AS "reporter",
        project_t.name AS "project",
        user_t2.realname AS "hotliner",
        CASE
            WHEN bug_t.priority = 40 THEN "high"
            WHEN bug_t.priority = 10 THEN "none"
            ELSE bug_t.priority
        END AS "priority",
        CASE
            WHEN bug_t.status = 10 THEN "new"
            WHEN bug_t.status = 30 THEN "acknowledged"
            WHEN bug_t.status = 50 THEN "assigned"
            WHEN bug_t.status = 60 THEN "ordirope"
            WHEN bug_t.status = 61 THEN "talentia"
            WHEN bug_t.status = 65 THEN "change review"
            WHEN bug_t.status = 70 THEN "testing"
            WHEN bug_t.status = 80 THEN "resolved"
            WHEN bug_t.status = 90 THEN "closed"
        END AS "status",
        bug_t.summary AS "ticket summary",
        category_t.name AS "category",
        DATE_FORMAT(FROM_UNIXTIME(bug_t.last_updated),"%d/%m/%Y") AS "last updated",
        DATE_FORMAT(FROM_UNIXTIME(bug_t.date_submitted),"%d/%m/%Y") AS "date submitted",
        GROUP_CONCAT(
            CONCAT(
            CONCAT(DATE_FORMAT(FROM_UNIXTIME(bugnote_t.date_submitted),"%d/%m/%Y"), ' - ', user_t3.realname)
            ,": ",bugnotetext_t.note)
            SEPARATOR "|") AS "note history"
        
FROM 	`mantis_bug_table` AS bug_t
INNER JOIN `mantis_project_table` AS project_t ON bug_t.project_id = project_t.id
INNER JOIN `mantis_user_table` AS user_t ON
bug_t.reporter_id = user_t.id
INNER JOIN `mantis_user_table` AS user_t2 ON
bug_t.handler_id = user_t2.id
INNER JOIN `mantis_category_table` AS category_t ON
bug_t.category_id = category_t.id

/* Jointure avec les notes */
RIGHT OUTER JOIN `mantis_bugnote_table` AS bugnote_t ON
bug_t.id = bugnote_t.bug_id

/* Jointure avec les textes de notes */
INNER JOIN `mantis_bugnote_text_table` AS bugnotetext_t ON
bugnote_t.id = bugnotetext_t.id 

/* Jointure user pour avoir la personne qui a cr�� la note */
INNER JOIN `mantis_user_table` AS user_t3 ON
bugnote_t.reporter_id = user_t3.id

/* Exclusion des notes priv�es */
WHERE bugnote_t.view_state <> 50

GROUP BY bug_t.id
ORDER BY bug_t.id, bugnote_t.id
