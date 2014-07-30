-- 2014-07-29-add-index-ExamMeta.sql
-- This is a database migration that updates an existing database
-- so that the table names match the codebase.
-- this is important for linux systems

use `capricorn`;

alter table ExamMeta add index (`AccessionNumber`);