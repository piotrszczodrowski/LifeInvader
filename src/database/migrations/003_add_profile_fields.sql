ALTER TABLE `users`
    ADD COLUMN `bio` VARCHAR(255) NULL,
  ADD COLUMN `avatar_path` VARCHAR(255) DEFAULT '/uploads/avatars/default.png',
  ADD COLUMN `birth_date` DATE NULL,
  ADD COLUMN `project_evaluation` VARCHAR(50) NULL;