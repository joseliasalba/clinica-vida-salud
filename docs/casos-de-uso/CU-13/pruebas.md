# CU-13 — Pruebas

**Resultado observado en `c640f0a`:** 26 pruebas aprobadas, 1.142 aserciones en conjunto. La ejecución usó PostgreSQL local aislado y datos de prueba.

```text
php vendor/bin/phpunit tests/Feature/LucasClinicalWorkflowTest.php tests/Feature/Estudios/StudyManagementTest.php tests/Feature/ResponsiveLayoutTest.php --stop-on-defect
```

El caso de prueba filtra canceladas, verifica dos citas de un mismo paciente, excluye una cita ajena al filtro y deniega el acceso a recepción.

También compiló la interfaz con `npm run build`. Estas verificaciones no acreditan el funcionamiento en producción ni la geometría visual.

**Pendiente:** Comprobar las combinaciones de filtros y el diseño en navegador. Confirmar si la entrega requiere un reporte de fichas de pacientes independiente del reporte de citas.
