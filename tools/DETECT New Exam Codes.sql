SELECT * from (
select em.AccessionNumber, em.ExamCode, em.Organization, ecd.Description from `exammeta`             as em
LEFT JOIN ExamCodeDefinition as ecd ON (ecd.ExamCode=em.ExamCode AND ecd.ORG=em.Organization)
WHERE CompletedDTTM > '2015-2-7'
AND ResidentYear < 99
GROUP BY em.ExamCode, em.Organization
               ) AS `table`
WHERE Description IS NULL
