-- 2014-07-22-rename-tables.sql
-- This is a database migration that updates an existing database
-- so that the table names match the codebase.
-- this is important for linux systems

use `capricorn`;

rename table `attendingiddefinition` TO `AttendingIDDefinition`;

rename table `examcodedefinition` TO `ExamCodeDefinition`;

rename table `exammeta` TO `ExamMeta`;

rename table `loginmember` TO `LoginMember`;

rename table `residentcounts` TO `ResidentCounts`;

rename table `residentiddefinition` TO `ResidentIDDefinition`;

rename table `residentrotation` TO `ResidentRotation`;

rename table `residentrotationraw` TO `ResidentRotationRaw`;
