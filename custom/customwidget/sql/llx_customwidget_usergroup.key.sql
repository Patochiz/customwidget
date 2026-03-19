-- Module customwidget - Index table liaison
ALTER TABLE llx_customwidget_usergroup ADD INDEX idx_cwug_widget (fk_customwidget);
ALTER TABLE llx_customwidget_usergroup ADD INDEX idx_cwug_group (fk_usergroup);
ALTER TABLE llx_customwidget_usergroup ADD UNIQUE INDEX uk_cwug_pair (fk_customwidget, fk_usergroup);
