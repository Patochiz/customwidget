-- Module customwidget - Index table principale
ALTER TABLE llx_customwidget ADD INDEX idx_customwidget_active (active);
ALTER TABLE llx_customwidget ADD INDEX idx_customwidget_type (widget_type);
ALTER TABLE llx_customwidget ADD INDEX idx_customwidget_entity (entity);
ALTER TABLE llx_customwidget ADD UNIQUE INDEX uk_customwidget_ref (ref, entity);
