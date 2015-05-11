/* Time tracking report */
SELECT	bugnote_t.bug_id AS "ticket #",
		user_t.realname AS "reporter",
        /* Formattage temps sans les secondes */
        TIME_FORMAT(
            /* Conversion du nbre de minute en format TIME: */
            SEC_TO_TIME(SUM(time_tracking)*60)
            ,"%H:%i") AS "total time"
FROM `mantis_bugnote_table` AS bugnote_t

INNER JOIN `mantis_user_table` AS user_t ON
bugnote_t.reporter_id = user_t.id
    
WHERE bugnote_t.time_tracking <> 0

GROUP BY bugnote_t.reporter_id, bugnote_t.bug_id
    