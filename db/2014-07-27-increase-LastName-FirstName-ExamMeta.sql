-- 2014-07-22-increase-LastName-FirstName-ExamMeta.sql
-- This is a database migration that updates an existing database
-- to accept larger values for LastName and FirstName
-- DICOM allows 64 characters for the name including first, last, and middle

use `capricorn`;

alter table ExamMeta modify column LastName varchar(32) NOT NULL;
alter table ExamMeta modify column FirstName varchar(32) NOT NULL;