# CU-16 — Pruebas

**Resultado observado en `c640f0a`:** 26 pruebas aprobadas, 1.142 aserciones en conjunto. La ejecución usó PostgreSQL local aislado y datos de prueba.

```text
php vendor/bin/phpunit tests/Feature/LucasClinicalWorkflowTest.php tests/Feature/Estudios/StudyManagementTest.php tests/Feature/ResponsiveLayoutTest.php --stop-on-defect
```

La regresión StudyManagementTest pasó junto con la prueba que crea una consulta desde CU-15 y luego registra una solicitud real de CU-16.

También compiló la interfaz con `npm run build`. Estas verificaciones no acreditan el funcionamiento en producción ni la geometría visual.

**Pendiente:** Configurar almacenamiento privado persistente de PDF antes de usar archivos adjuntos en Vercel y repetir publicación y descarga allí.
