-- 2014-07-29-add-id-to-ResidentRotations.sql
-- This migration adds an ID column to ResidentRotation

use `capricorn`;

alter table ResidentRotation add column `ID` int(11) KEY NOT NULL AUTO_INCREMENT;