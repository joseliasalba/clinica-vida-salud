# CU-14 — Pruebas

**Resultado observado en `c640f0a`:** 26 pruebas aprobadas, 1.142 aserciones en conjunto. La ejecución usó PostgreSQL local aislado y datos de prueba.

```text
php vendor/bin/phpunit tests/Feature/LucasClinicalWorkflowTest.php tests/Feature/Estudios/StudyManagementTest.php tests/Feature/ResponsiveLayoutTest.php --stop-on-defect
```

Se comprobó guardado de alergia y contacto, aislamiento frente a un ID ajeno, lectura del perfil y denegación a recepción.

También compiló la interfaz con `npm run build`. Estas verificaciones no acreditan el funcionamiento en producción ni la geometría visual.

**Pendiente:** Revisar en navegador el formulario, los campos incompletos y la eliminación del contacto principal.
