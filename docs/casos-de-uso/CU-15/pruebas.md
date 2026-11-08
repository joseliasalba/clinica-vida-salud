# CU-15 — Pruebas

**Resultado observado en `c640f0a`:** 26 pruebas aprobadas, 1.142 aserciones en conjunto. La ejecución usó PostgreSQL local aislado y datos de prueba.

```text
php vendor/bin/phpunit tests/Feature/LucasClinicalWorkflowTest.php tests/Feature/Estudios/StudyManagementTest.php tests/Feature/ResponsiveLayoutTest.php --stop-on-defect
```

Se verificaron acceso del médico asignado, rechazo a otro médico, persistencia de consulta e historia y tratamiento, rechazo de duplicado y enlace funcional al CU-16.

También compiló la interfaz con `npm run build`. Estas verificaciones no acreditan el funcionamiento en producción ni la geometría visual.

**Pendiente:** Revisión visual autenticada y comprobación de los campos opcionales en navegador.
