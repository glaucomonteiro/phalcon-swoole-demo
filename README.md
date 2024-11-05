# Open Swoole + Phalcon 5 Demo

This is a sample code for running an open swoole server using the phalcon framework.

It also includes the base fixes for running tests with behat, so that functions tested can rely on coroutine contexts, and the same fix is applied to the tasks, so the cli can also use functions that rely on coroutine contexts.

This demo is the result of a conversion from a real project that used the phalcon micro framework and that now uses only parts of phalcon due to memory leaks.

The database connection pool is started per worker, but can be started globally and shared between all workers.
