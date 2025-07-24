/**
 * Dashboard Escritor Mejorado - JavaScript
 * Versión 2.0 - Funcionalidades completas
 */

// Variables globales
let escritorData = null;
let dashboardData = null;
let analyticsData = null;
let notificacionesData = null;
let charts = {};

// Inicialización
function checkAuthentication() {
    const token = localStorage.getItem('session_token');
    const userData = localStorage.getItem('user_data');
    if (!token || !userData) {
        limpiarSesion();
        return false;
    }
    try {
        const user = JSON.parse(userData);
        if (user.rol !== 'escritor' && user.rol !== 'admin') {
            limpiarSesion();
            return false;
        }
        // Verificar usuario actual
        const currentSessionUser = sessionStorage.getItem('current_user_id');
        if (currentSessionUser && currentSessionUser !== user.id.toString()) {
            limpiarSesion();
            return false;
        }
        sessionStorage.setItem('current_user_id', user.id.toString());
        return true;
    } catch (error) {
        limpiarSesion();
        return false;
    }
}

function limpiarSesion() {
    localStorage.removeItem('session_token');
    localStorage.removeItem('user_data');
    sessionStorage.removeItem('current_user_id');
}

document.addEventListener('DOMContentLoaded', async function() {
    console.log('🚀 Iniciando Dashboard Escritor Mejorado v2.0...');
    
    if (!checkAuthentication()) {
        window.location.href = 'login.html';
        return;
    }

    // Cargar datos iniciales
    await loadEscritorData();
    
    // Configurar navegación
    setupNavigation();
    
    // Configurar eventos
    setupEventListeners();
    
    // Cargar pestaña inicial
    loadInitialTab();
    
    // Iniciar actualizaciones automáticas
    startAutoRefresh();
    
    console.log('✅ Dashboard escritor inicializado correctamente');
});

// ========================================
// FUNCIONES DE AUTENTICACIÓN Y DATOS
// ========================================

async function loadEscritorData() {
    console.log('🚀 Cargando datos del escritor...');
    try {
        const response = await fetch('api/escritores/dashboard.php', {
            credentials: 'include'
        });
        const data = await response.json();
        
        console.log('📊 Respuesta del servidor:', data);
        
        if (data.success) {
            escritorData = data.escritor;
            dashboardData = data;
            
            console.log('✅ Datos cargados correctamente');
            console.log('📈 Datos de gráficos:', data.datos_graficos);
            console.log('📋 Ventas recientes:', data.ventas_recientes);
            console.log('📚 Libros:', data.libros);
            
            updateEscritorInfo();
            updateDashboardStats();
        } else {
            console.error('❌ Error en la respuesta:', data.error);
            showError('Error al cargar datos del escritor');
        }
    } catch (error) {
        console.error('❌ Error cargando datos del escritor:', error);
        showError('Error de conexión');
    }
}

// ========================================
// FUNCIONES DE INTERFAZ
// ========================================

function setupNavigation() {
    const navItems = document.querySelectorAll('.dashboard-nav li');
    
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Remover clase activa
            navItems.forEach(nav => nav.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            
            // Activar pestaña seleccionada
            this.classList.add('active');
            const targetTab = document.getElementById(tabId);
            if (targetTab) {
                targetTab.classList.add('active');
                if (tabId === 'configuracion') {
                    console.log('🟢 Pestaña configuración seleccionada');
                }
                loadTabContent(tabId);
            } else {
                console.warn('⚠️ No se encontró el tab con id:', tabId);
            }
        });
    });
}

function setupEventListeners() {
    // Analytics
    document.getElementById('actualizarAnalytics')?.addEventListener('click', loadAnalyticsTab);
    document.getElementById('periodoAnalytics')?.addEventListener('change', loadAnalyticsTab);
    
    // Subir libro
    document.getElementById('formSubirLibro')?.addEventListener('submit', handleSubmitLibro);
    document.getElementById('btnNuevoLibro')?.addEventListener('click', () => {
        document.querySelector('li[data-tab="subir-libro"]').click();
    });
    
    // Filtros de libros
    document.getElementById('filtroEstado')?.addEventListener('change', loadLibrosTab);
    document.getElementById('filtroCategoria')?.addEventListener('change', loadLibrosTab);
    
    // Notificaciones
    document.getElementById('marcarTodasLeidas')?.addEventListener('click', marcarTodasNotificacionesLeidas);
    document.getElementById('actualizarNotificaciones')?.addEventListener('click', loadNotificacionesTab);
    document.getElementById('filtroNoLeidas')?.addEventListener('change', loadNotificacionesTab);
    document.getElementById('notificationsBadge')?.addEventListener('click', () => {
        document.querySelector('li[data-tab="notificaciones"]').click();
    });
    
    // Configuración
    document.getElementById('formPerfil')?.addEventListener('submit', handleSubmitPerfil);
    document.getElementById('formNotificaciones')?.addEventListener('submit', handleSubmitNotificaciones);
    document.getElementById('formSeguridad')?.addEventListener('submit', handleSubmitSeguridad);
    document.getElementById('formPagos')?.addEventListener('submit', handleSubmitPagos);
    
    // Logout
    document.getElementById('logoutBtn')?.addEventListener('click', logout);
}

function loadInitialTab() {
    const activeTab = document.querySelector('.dashboard-nav li.active');
    if (activeTab) {
        const tabId = activeTab.getAttribute('data-tab');
        loadTabContent(tabId);
    }
}

async function loadTabContent(tabID) {
    console.log(`📊 Cargando pestaña: ${tabID}`);
    
    switch (tabID) {
        case 'inicio':
            await loadInicioTab();
            break;
        case 'analytics':
            await loadAnalyticsTab();
            break;
        case 'libros':
            await loadLibrosTab();
            break;
        case 'subir-libro':
            // No necesita carga adicional
            break;
        case 'royalties':
            await loadRoyaltiesTab();
            break;
        case 'notificaciones':
            await loadNotificacionesTab();
            break;
        case 'configuracion':
            await loadConfiguracionTab();
            break;
        case 'mi-testimonio':
            // Eliminar funciones y llamadas relacionadas con testimonios admin
            break;
    }
}

// ========================================
// PESTAÑA INICIO
// ========================================

async function loadInicioTab() {
    console.log('🔍 Cargando pestaña Inicio...');
    console.log('🔍 dashboardData:', dashboardData);
    
    if (!dashboardData) {
        console.log('❌ dashboardData no disponible');
        return;
    }
    
    // Actualizar estadísticas
    updateDashboardStats();
    
    // Crear gráfico de tendencias
    createTendenciasChart();
    
    // Cargar libros top
    updateLibrosTop();
    
    // Cargar actividad reciente
    updateActividadReciente();
}

function updateDashboardStats() {
    if (!dashboardData || !dashboardData.estadisticas) return;
    
    const stats = dashboardData.estadisticas;
    
    updateElement('totalLibros', stats.total_libros || 0);
    updateElement('totalVentas', stats.total_ventas || 0);
    updateElement('gananciasTotales', formatCurrency(stats.ganancias_totales || 0));
    updateElement('gananciaPromedio', formatCurrency(stats.ganancia_promedio_por_venta || 0));
}

function createTendenciasChart() {
    if (!dashboardData || !dashboardData.datos_graficos) return;
    
    const ctx = document.getElementById('chartTendencias');
    if (!ctx) return;
    
    if (charts.tendencias) {
        charts.tendencias.destroy();
    }
    
    const datos = dashboardData.datos_graficos;
    const labels = datos.map(d => formatMonth(d.mes));
    const ventas = datos.map(d => d.ventas);
    const ganancias = datos.map(d => d.ganancias);
    
    charts.tendencias = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Ventas',
                    data: ventas,
                    borderColor: '#5a67d8',
                    backgroundColor: 'rgba(90, 103, 216, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y'
                },
                {
                    label: 'Ganancias',
                    data: ganancias,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
}

function updateLibrosTop() {
    console.log('🔍 Actualizando libros top...');
    console.log('🔍 dashboardData.libros_top:', dashboardData?.libros_top);
    
    if (!dashboardData || !dashboardData.libros_top) {
        console.log('❌ No hay datos de libros top');
        return;
    }
    
    const container = document.getElementById('librosTop');
    if (!container) {
        console.log('❌ No se encontró el contenedor librosTop');
        return;
    }
    
    const libros = dashboardData.libros_top;
    
    if (libros.length === 0) {
        console.log('❌ No hay libros para mostrar');
        container.innerHTML = '<p class="empty-state">No tienes libros publicados aún</p>';
        return;
    }
    
    console.log('🔍 Renderizando libros:', libros);
    
    const html = libros.map(libro => `
        <div class="libro-item">
            <div class="libro-info">
                <h4>${libro.titulo}</h4>
                <p>${libro.ventas || 0} ventas</p>
            </div>
            <div class="libro-stats">
                <span>Ganancias</span>
                <span>${formatCurrency(libro.ganancias_libro || 0)}</span>
            </div>
        </div>
    `).join('');
    
    console.log('🔍 HTML generado para libros:', html);
    container.innerHTML = html;
}

function updateActividadReciente() {
    console.log('🔍 Actualizando actividad reciente...');
    console.log('🔍 dashboardData.ventas_recientes:', dashboardData?.ventas_recientes);
    
    if (!dashboardData || !dashboardData.ventas_recientes) {
        console.log('❌ No hay datos de ventas recientes');
        return;
    }
    
    const container = document.getElementById('actividadReciente');
    if (!container) {
        console.log('❌ No se encontró el contenedor actividadReciente');
        return;
    }
    
    const ventas = dashboardData.ventas_recientes;
    
    if (ventas.length === 0) {
        console.log('❌ No hay ventas para mostrar');
        container.innerHTML = `
            <div class="empty-activity">
                <div class="empty-icon">📊</div>
                <h4>Sin actividad reciente</h4>
                <p>Cuando tengas nuevas ventas, aparecerán aquí</p>
            </div>
        `;
        return;
    }
    
    console.log('🔍 Renderizando ventas:', ventas);
    
    const html = `
        <div class="actividad-table-container">
            <table class="actividad-table">
                <thead>
                    <tr>
                        <th class="th-fecha">Fecha</th>
                        <th class="th-libro">Libro</th>
                        <th class="th-ganancia">Ganancia</th>
                    </tr>
                </thead>
                <tbody>
                    ${ventas.map(venta => `
                        <tr class="actividad-row" data-venta-id="${venta.id}">
                            <td class="actividad-fecha-cell">
                                <div class="fecha-compacta">
                                    <span class="fecha-dia">${new Date(venta.fecha_venta).toLocaleDateString('es-CO', { day: 'numeric' })}</span>
                                    <span class="fecha-mes">${new Date(venta.fecha_venta).toLocaleDateString('es-CO', { month: 'short' })}</span>
                                </div>
                            </td>
                            <td class="actividad-libro">
                                <span class="libro-titulo">${venta.libro_titulo}</span>
                            </td>
                            <td class="actividad-ganancia">
                                <span class="ganancia-monto">${formatCurrency(venta.ganancia)}</span>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
    
    console.log('🔍 HTML generado para tabla de ventas:', html);
    container.innerHTML = html;
    
    // Agregar animación de entrada
    setTimeout(() => {
        const rows = container.querySelectorAll('.actividad-row');
        rows.forEach((row, index) => {
            row.style.animationDelay = `${index * 0.05}s`;
            row.classList.add('animate-in');
        });
    }, 100);
}

// ========================================
// PESTAÑA ANALYTICS
// ========================================

async function loadAnalyticsTab() {
    try {
        showLoading('Cargando analytics...');
        
        const periodo = document.getElementById('periodoAnalytics').value;
        const escritor_id = escritorData?.escritor_id || escritorData?.id;
        
        console.log('🔍 Cargando analytics para escritor_id:', escritor_id, 'periodo:', periodo);
        console.log('🔍 escritorData:', escritorData);
        
        const response = await fetch(`api/escritores/analytics.php?escritor_id=${escritor_id}&periodo=${periodo}`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        console.log('🔍 Respuesta de analytics:', data);
        
        if (data.success) {
            analyticsData = data;
            updateAnalyticsMetrics();
            createAnalyticsCharts();
            updateAfiliadosTop();
        } else {
            showError('Error al cargar analytics: ' + (data.error || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error cargando analytics:', error);
        showError('Error de conexión');
    } finally {
        hideLoading();
    }
}

function updateAnalyticsMetrics() {
    if (!analyticsData) return;
    
    const metrics = analyticsData.metricas_generales;
    
    // Actualizar métricas en la pestaña analytics si existen
    updateElement('totalVentasPeriodo', metrics.total_ventas_periodo || 0);
    updateElement('gananciasPeriodo', formatCurrency(metrics.ganancias_periodo || 0));
    updateElement('librosActivos', metrics.libros_activos || 0);
}

function createAnalyticsCharts() {
    if (!analyticsData) return;
    
    // Gráfico de tendencias mensuales
    createTendenciasMensualesChart();
    
    // Gráfico de categorías
    createCategoriasChart();
    
    // Gráfico de precios
    createPreciosChart();
    
    // Gráfico de días de la semana
    createDiasSemanaChart();
}

function createTendenciasMensualesChart() {
    const ctx = document.getElementById('chartTendenciasMensuales');
    if (!ctx) return;
    
    if (charts.tendenciasMensuales) {
        charts.tendenciasMensuales.destroy();
    }
    
    const tendencias = analyticsData.tendencias_mensuales;
    const labels = tendencias.map(t => formatMonth(t.mes));
    const ventas = tendencias.map(t => t.ventas);
    const ganancias = tendencias.map(t => t.ganancias);
    
    charts.tendenciasMensuales = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Ventas',
                    data: ventas,
                    borderColor: '#5a67d8',
                    backgroundColor: 'rgba(90, 103, 216, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Ganancias',
                    data: ganancias,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function createCategoriasChart() {
    const ctx = document.getElementById('chartCategorias');
    if (!ctx) return;
    
    if (charts.categorias) {
        charts.categorias.destroy();
    }
    
    const categorias = analyticsData.analisis_categorias;
    const labels = categorias.map(c => c.categoria);
    const ganancias = categorias.map(c => c.ganancias_categoria);
    
    charts.categorias = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: ganancias,
                backgroundColor: [
                    '#5a67d8',
                    '#f59e0b',
                    '#10b981',
                    '#ef4444',
                    '#8b5cf6',
                    '#06b6d4'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
}

function createPreciosChart() {
    const ctx = document.getElementById('chartPrecios');
    if (!ctx) return;
    
    if (charts.precios) {
        charts.precios.destroy();
    }
    
    const precios = analyticsData.analisis_precios;
    const labels = precios.map(p => p.rango_precio);
    const ganancias = precios.map(p => p.ganancias_rango);
    
    charts.precios = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Ganancias por rango de precio',
                data: ganancias,
                backgroundColor: '#10b981'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function createDiasSemanaChart() {
    const ctx = document.getElementById('chartDiasSemana');
    if (!ctx) return;
    
    if (charts.diasSemana) {
        charts.diasSemana.destroy();
    }
    
    const dias = analyticsData.analisis_dias_semana;
    const labels = dias.map(d => d.dia_semana);
    const ventas = dias.map(d => d.ventas_dia);
    
    charts.diasSemana = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Ventas por día',
                data: ventas,
                backgroundColor: '#3b82f6'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function updateAfiliadosTop() {
    if (!analyticsData || !analyticsData.afiliados_top) return;
    
    const container = document.getElementById('afiliadosTop');
    if (!container) return;
    
    const afiliados = analyticsData.afiliados_top;
    
    if (afiliados.length === 0) {
        container.innerHTML = '<p class="empty-state">No hay datos de afiliados</p>';
        return;
    }
    
    container.innerHTML = `
        <table>
            <thead>
                <tr>
                    <th>Afiliado</th>
                    <th>Ventas</th>
                    <th>Ganancias Generadas</th>
                </tr>
            </thead>
            <tbody>
                ${afiliados.map(afiliado => `
                    <tr>
                        <td>${afiliado.afiliado_nombre}</td>
                        <td>${afiliado.ventas_afiliado}</td>
                        <td>${formatCurrency(afiliado.ganancias_afiliado)}</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

// ========================================
// PESTAÑA LIBROS
// ========================================

async function loadLibrosTab() {
    if (!dashboardData || !dashboardData.libros) return;
    
    const estado = document.getElementById('filtroEstado').value;
    const categoria = document.getElementById('filtroCategoria').value;
    
    let libros = dashboardData.libros;
    
    // Aplicar filtros
    if (estado) {
        libros = libros.filter(libro => libro.estado === estado);
    }
    
    if (categoria) {
        libros = libros.filter(libro => libro.categoria === categoria);
    }
    
    renderLibros(libros);
}

function renderLibros(libros) {
    const container = document.getElementById('librosGrid');
    if (!container) return;
    
    if (libros.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <h3>No se encontraron libros</h3>
                <p>No hay libros que coincidan con los filtros seleccionados.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = libros.map(libro => `
        <div class="libro-card">
            ${libro.portada_url ? `<img src="${libro.portada_url}" alt="${libro.titulo}" class="libro-portada">` : ''}
            <h3 class="libro-titulo">${libro.titulo}</h3>
            <p class="libro-categoria"><strong>Categoría:</strong> ${libro.categoria || 'Sin categoría'}</p>
            <span class="libro-estado ${libro.estado}">${libro.estado}</span>
            <p class="libro-descripcion">${libro.descripcion.substring(0, 100)}...</p>
            <div class="libro-stats">
                <div class="libro-stat">
                    <strong>Precio:</strong> ${formatCurrency(libro.precio)}
                </div>
                <div class="libro-stat">
                    <strong>Ventas:</strong> ${libro.ventas_totales || 0}
                </div>
                <div class="libro-stat">
                    <strong>Ganancias:</strong> ${formatCurrency(libro.ganancias_libro || 0)}
                </div>
                <div class="libro-stat">
                    <strong>Publicado:</strong> ${formatDate(libro.fecha_publicacion)}
                </div>
            </div>
            <div class="libro-actions">
                <button onclick="editarLibro(${libro.id})" class="btn btn-secondary">Editar</button>
            </div>
        </div>
    `).join('');
}

// ========================================
// PESTAÑA SUBIR LIBRO
// ========================================

async function handleSubmitLibro(event) {
    event.preventDefault();
    
    const formData = new FormData();
    formData.append('escritor_id', escritorData?.id || 101);
    formData.append('titulo', document.getElementById('tituloLibro').value);
    formData.append('descripcion', document.getElementById('descripcionLibro').value);
    formData.append('precio', document.getElementById('precioLibro').value);
    formData.append('categoria', document.getElementById('categoriaLibro').value);
    formData.append('isbn', document.getElementById('isbnLibro').value);
    
    const archivoPDF = document.getElementById('archivoPDF').files[0];
    const portada = document.getElementById('portadaLibro').files[0];
    
    if (archivoPDF) {
        formData.append('archivo_pdf', archivoPDF);
    }
    
    if (portada) {
        formData.append('portada', portada);
    }
    
    try {
        showLoading('Subiendo libro...');
        
        const response = await fetch('api/escritores/subir_libro.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Libro subido exitosamente. Será revisado por nuestro equipo.');
            event.target.reset();
            
            // Recargar datos del dashboard
            await loadEscritorData();
        } else {
            showError(data.error || 'Error al subir el libro');
        }
    } catch (error) {
        console.error('Error subiendo libro:', error);
        showError('Error de conexión');
    } finally {
        hideLoading();
    }
}

// ========================================
// PESTAÑA ROYALTIES
// ========================================

async function loadRoyaltiesTab() {
    console.log('💰 Cargando pestaña de royalties...');
    console.log('dashboardData disponible:', !!dashboardData);
    console.log('dashboardData completo:', dashboardData);
    
    if (!dashboardData) {
        console.log('❌ No hay datos del dashboard disponibles');
        return;
    }
    
    // Actualizar resumen de royalties
    updateRoyaltiesSummary();
    
    // Esperar un poco para asegurar que el DOM esté listo
    setTimeout(() => {
        console.log('⏰ Timeout completado, creando gráficos...');
        // Crear gráficos de royalties
        createRoyaltiesCharts();
        
        // Cargar historial
        updateHistorialRoyalties();
    }, 100);
}

function updateRoyaltiesSummary() {
    if (!dashboardData || !dashboardData.estadisticas) return;
    
    const stats = dashboardData.estadisticas;
    
    updateElement('totalGanado', formatCurrency(stats.ganancias_totales || 0));
    updateElement('disponibleRetiro', formatCurrency(stats.ganancias_totales || 0)); // Simplificado
    updateElement('totalRetirado', formatCurrency(0)); // Por implementar
}

function createRoyaltiesCharts() {
    console.log('🔍 Creando gráficos de royalties...');
    console.log('dashboardData:', dashboardData);
    
    if (!dashboardData || !dashboardData.datos_graficos) {
        console.log('❌ No hay datos de gráficos disponibles');
        return;
    }
    
    console.log('📊 Datos de gráficos:', dashboardData.datos_graficos);
    
    // Gráfico de ganancias por mes
    createGananciasChart();
    
    // Gráfico de royalties por libro
    createRoyaltiesLibrosChart();
}

function createGananciasChart() {
    console.log('📈 Creando gráfico de ganancias...');
    const ctx = document.getElementById('chartGanancias');
    if (!ctx) {
        console.log('❌ Elemento chartGanancias no encontrado');
        return;
    }
    console.log('✅ Elemento chartGanancias encontrado');
    
    if (charts.ganancias) {
        charts.ganancias.destroy();
    }
    
    const datos = dashboardData.datos_graficos;
    console.log('Datos para gráfico de ganancias:', datos);
    
    const labels = datos.map(d => formatMonth(d.mes));
    const ganancias = datos.map(d => d.ganancias);
    
    console.log('Labels:', labels);
    console.log('Ganancias:', ganancias);
    
    charts.ganancias = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Ganancias Mensuales',
                data: ganancias,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function createRoyaltiesLibrosChart() {
    console.log('📊 Creando gráfico de royalties por libro...');
    const ctx = document.getElementById('chartRoyaltiesLibros');
    if (!ctx) {
        console.log('❌ Elemento chartRoyaltiesLibros no encontrado');
        return;
    }
    console.log('✅ Elemento chartRoyaltiesLibros encontrado');
    
    if (charts.royaltiesLibros) {
        charts.royaltiesLibros.destroy();
    }
    
    if (!dashboardData || !dashboardData.libros) return;
    
    const libros = dashboardData.libros.slice(0, 8);
    console.log('Libros para gráfico:', libros);
    
    const labels = libros.map(l => l.titulo.substring(0, 20) + '...');
    const ganancias = libros.map(l => l.ganancias_libro || 0);
    
    console.log('Labels libros:', labels);
    console.log('Ganancias libros:', ganancias);
    
    charts.royaltiesLibros = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Ganancias por Libro',
                data: ganancias,
                backgroundColor: '#5a67d8'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function updateHistorialRoyalties() {
    console.log('📋 Actualizando historial de royalties...');
    console.log('dashboardData:', dashboardData);
    
    if (!dashboardData || !dashboardData.ventas_recientes) {
        console.log('❌ No hay datos de ventas recientes');
        return;
    }
    
    const container = document.getElementById('historialRoyalties');
    if (!container) {
        console.log('❌ Elemento historialRoyalties no encontrado');
        return;
    }
    
    const ventas = dashboardData.ventas_recientes;
    console.log('Ventas recientes:', ventas);
    
    if (ventas.length === 0) {
        container.innerHTML = '<p class="empty-state">No hay historial de royalties</p>';
        return;
    }
    
    container.innerHTML = `
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Libro</th>
                    <th>Ganancia (30%)</th>
                </tr>
            </thead>
            <tbody>
                ${ventas.map(venta => `
                    <tr>
                        <td>${formatDate(venta.fecha_venta)}</td>
                        <td>${venta.libro_titulo}</td>
                        <td>${formatCurrency(venta.ganancia)}</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
    
    console.log('✅ Historial de royalties actualizado');
}

// ========================================
// PESTAÑA NOTIFICACIONES
// ========================================

async function loadNotificacionesTab() {
    try {
        const noLeidas = document.getElementById('filtroNoLeidas').checked;
        const url = `api/escritores/notificaciones.php?no_leidas=${noLeidas}`;
        
        const response = await fetch(url, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            notificacionesData = data;
            renderNotificaciones();
            updateContadorNotificaciones();
        }
    } catch (error) {
        console.error('Error cargando notificaciones:', error);
    }
}

function renderNotificaciones() {
    const container = document.getElementById('notificacionesList');
    if (!container) return;
    
    if (!notificacionesData.notificaciones || notificacionesData.notificaciones.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <h3>No hay notificaciones</h3>
                <p>Cuando tengas nuevas actividades, aparecerán aquí.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = notificacionesData.notificaciones.map(notif => `
        <div class="notificacion-item ${notif.leida ? 'leida' : 'no-leida'}" data-id="${notif.id}">
            <div class="notificacion-icon">
                ${getNotificacionIcon(notif.tipo)}
            </div>
            <div class="notificacion-content">
                <h4>${notif.titulo}</h4>
                <p>${notif.mensaje}</p>
                <small>${formatDate(notif.fecha_creacion)}</small>
            </div>
            <div class="notificacion-actions">
                ${!notif.leida ? `<button onclick="marcarNotificacionLeida(${notif.id})" class="btn btn-sm">Marcar leída</button>` : ''}
            </div>
        </div>
    `).join('');
}

function getNotificacionIcon(tipo) {
    const icons = {
        'venta': '💰',
        'royalty': '💵',
        'aprobacion': '✅',
        'rechazo': '❌',
        'comentario': '💬',
        'sistema': '🔔'
    };
    return icons[tipo] || '📢';
}

function updateContadorNotificaciones() {
    const contador = document.getElementById('notificationsCount');
    if (contador && notificacionesData) {
        contador.textContent = notificacionesData.no_leidas;
    }
}

async function marcarNotificacionLeida(notificacionId) {
    try {
        const response = await fetch('api/escritores/notificaciones.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ notificacion_id: notificacionId }),
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            await loadNotificacionesTab();
        }
    } catch (error) {
        console.error('Error marcando notificación:', error);
    }
}

async function marcarTodasNotificacionesLeidas() {
    try {
        const response = await fetch('api/escritores/notificaciones.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ marcar_todas: true }),
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Todas las notificaciones marcadas como leídas');
            await loadNotificacionesTab();
        }
    } catch (error) {
        console.error('Error marcando notificaciones:', error);
    }
}

// ========================================
// PESTAÑA CONFIGURACIÓN
// ========================================

async function loadConfiguracionTab() {
    console.log('🔧 Ejecutando loadConfiguracionTab...');
    // Si no hay datos, intentar cargarlos
    if (!escritorData) {
        console.log('🔧 escritorData vacío, intentando cargar...');
        await loadEscritorData();
    }
    // Llenar campos si hay datos
    if (escritorData) {
        console.log('🔧 escritorData:', escritorData);
        const nombreInput = document.getElementById('nombrePerfil');
        const emailInput = document.getElementById('emailPerfil');
        const bioInput = document.getElementById('biografiaPerfil');
        if (!nombreInput || !emailInput || !bioInput) {
            console.warn('⚠️ Algún campo de configuración no existe en el DOM:', {
                nombreInput, emailInput, bioInput
            });
            return;
        }
        nombreInput.value = escritorData.nombre || '';
        emailInput.value = escritorData.email || '';
        bioInput.value = escritorData.biografia || '';
        console.log('🔧 nombrePerfil:', nombreInput.value);
        console.log('🔧 emailPerfil:', emailInput.value);
        console.log('🔧 biografiaPerfil:', bioInput.value);
    } else {
        showError('No se pudo cargar la información del perfil.');
    }
    // Cargar configuración de notificaciones
    await loadConfiguracionNotificaciones();
}

async function loadConfiguracionNotificaciones() {
    try {
        const response = await fetch('api/escritores/notificaciones.php', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success && data.configuracion) {
            const config = data.configuracion;
            
            document.getElementById('notifVentas').checked = config.notif_ventas;
            document.getElementById('notifRoyalties').checked = config.notif_royalties;
            document.getElementById('notifComentarios').checked = config.notif_comentarios;
            document.getElementById('notifAprobacion').checked = config.notif_aprobacion;
        }
    } catch (error) {
        console.error('Error cargando configuración:', error);
    }
}

async function handleSubmitPerfil(event) {
    event.preventDefault();
    
    const formData = {
        nombre: document.getElementById('nombrePerfil').value,
        email: document.getElementById('emailPerfil').value,
        biografia: document.getElementById('biografiaPerfil').value
    };
    
    try {
        const response = await fetch('api/escritores/actualizar_perfil.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData),
            credentials: 'include'
        });
        const data = await response.json();
        if (data.success) {
            showSuccess('Perfil actualizado correctamente');
            // Actualizar datos locales
            if (escritorData) {
                escritorData.nombre = formData.nombre;
                escritorData.email = formData.email;
                escritorData.biografia = formData.biografia;
            }
            updateEscritorInfo();
        } else {
            showError(data.error || 'Error al actualizar perfil');
        }
    } catch (error) {
        console.error('Error actualizando perfil:', error);
        showError('Error al actualizar perfil');
    }
}

async function handleSubmitNotificaciones(event) {
    event.preventDefault();
    
    const configuracion = {
        notif_ventas: document.getElementById('notifVentas').checked,
        notif_royalties: document.getElementById('notifRoyalties').checked,
        notif_comentarios: document.getElementById('notifComentarios').checked,
        notif_aprobacion: document.getElementById('notifAprobacion').checked
    };
    
    try {
        const response = await fetch('api/escritores/notificaciones.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(configuracion),
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Preferencias guardadas correctamente');
        } else {
            showError('Error al guardar preferencias');
        }
    } catch (error) {
        console.error('Error guardando preferencias:', error);
        showError('Error de conexión');
    }
}

async function handleSubmitSeguridad(event) {
    event.preventDefault();
    
    const passwordActual = document.getElementById('passwordActual').value;
    const passwordNueva = document.getElementById('passwordNueva').value;
    const passwordConfirmar = document.getElementById('passwordConfirmar').value;
    
    if (passwordNueva !== passwordConfirmar) {
        showError('Las contraseñas no coinciden');
        return;
    }
    
    try {
        // Aquí iría la llamada al API para cambiar contraseña
        showSuccess('Contraseña cambiada correctamente');
        event.target.reset();
    } catch (error) {
        console.error('Error cambiando contraseña:', error);
        showError('Error al cambiar contraseña');
    }
}

async function handleSubmitPagos(event) {
    event.preventDefault();
    
    const formData = {
        metodo_pago: document.getElementById('metodoPago').value,
        numero_cuenta: document.getElementById('numeroCuenta').value,
        titular_cuenta: document.getElementById('titularCuenta').value,
        monto_minimo_retiro: document.getElementById('montoMinimoRetiro').value
    };
    
    try {
        // Aquí iría la llamada al API para guardar configuración de pagos
        showSuccess('Configuración de pagos guardada');
    } catch (error) {
        console.error('Error guardando configuración de pagos:', error);
        showError('Error al guardar configuración');
    }
}

// ========================================
// FUNCIONES AUXILIARES
// ========================================

function updateEscritorInfo() {
    if (!escritorData) return;
    
    const nombreElement = document.getElementById('escritorNombre');
    if (nombreElement) {
        nombreElement.textContent = escritorData.nombre || 'Escritor';
    }
}

function updateElement(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(amount);
}

function formatDate(dateString) {
    if (!dateString) return 'No disponible';
    
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'Fecha inválida';
        
        return date.toLocaleDateString('es-CO', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        console.error('Error formateando fecha:', dateString, error);
        return 'Fecha inválida';
    }
}

function formatMonth(monthString) {
    const [year, month] = monthString.split('-');
    return new Date(year, month - 1).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short'
    });
}

function showLoading(message = 'Cargando...') {
    console.log('Loading:', message);
}

function hideLoading() {
    console.log('Loading hidden');
}

function showError(message) {
    console.error('Error:', message);
    alert('Error: ' + message);
}

function showSuccess(message) {
    console.log('Success:', message);
    alert('Éxito: ' + message);
}

function startAutoRefresh() {
    // Actualizar notificaciones cada 30 segundos
    setInterval(() => {
        if (document.querySelector('#notificaciones.active')) {
            loadNotificacionesTab();
        }
    }, 30000);
}

function logout() {
    localStorage.removeItem('session_token');
    localStorage.removeItem('user_data');
    window.location.href = 'index.html';
}

// ========================================
// FUNCIONES PARA FUTURAS IMPLEMENTACIONES
// ========================================

function editarLibro(libroId) {
    console.log('Editar libro:', libroId);
    
    // Buscar el libro en los datos
    const libro = dashboardData?.libros?.find(l => l.id == libroId);
    if (!libro) {
        showError('Libro no encontrado');
        return;
    }
    
    // Llenar el formulario de edición (solo categoría y descripción)
    document.getElementById('editLibroId').value = libro.id;
    document.getElementById('editTitulo').value = libro.titulo;
    document.getElementById('editTitulo').disabled = true; // No editable
    document.getElementById('editDescripcion').value = libro.descripcion;
    document.getElementById('editPrecio').value = libro.precio;
    document.getElementById('editPrecio').disabled = true; // No editable
    document.getElementById('editCategoria').value = libro.categoria || '';
    
    // Mostrar el modal de edición
    const modal = document.getElementById('modalEditarLibro');
    if (modal) {
        modal.style.display = 'block';
    } else {
        showError('Modal de edición no encontrado');
    }
}



function cerrarModalEditar() {
    const modal = document.getElementById('modalEditarLibro');
    if (modal) {
        modal.style.display = 'none';
    }
}

// ========================================
// EXPORTAR FUNCIONES PARA USO GLOBAL
// ========================================

window.loadAnalyticsTab = loadAnalyticsTab;
window.loadLibrosTab = loadLibrosTab;
window.loadNotificacionesTab = loadNotificacionesTab;
window.marcarNotificacionLeida = marcarNotificacionLeida;
window.marcarTodasNotificacionesLeidas = marcarTodasNotificacionesLeidas;
window.editarLibro = editarLibro;
window.cerrarModalEditar = cerrarModalEditar;
window.logout = logout; 

// =============================
// ENVÍO DE TESTIMONIO (pestaña mi-testimonio)
// =============================

document.addEventListener('DOMContentLoaded', function() {
  const formTestimonio = document.getElementById('formTestimonio');
  if (formTestimonio) {
    formTestimonio.addEventListener('submit', handleSubmitTestimonio);
  }
  
  const formEditarLibro = document.getElementById('formEditarLibro');
  if (formEditarLibro) {
    formEditarLibro.addEventListener('submit', handleSubmitEditarLibro);
  }
});

async function handleSubmitTestimonio(event) {
  event.preventDefault();
  const form = event.target;
  const submitBtn = document.getElementById('btnEnviarTestimonio');
  const mensajeDiv = document.getElementById('mensajeTestimonio');

  // Validar formulario
  const testimonio = document.getElementById('testimonioTexto').value.trim();
  const aceptarPublicacion = document.getElementById('aceptarPublicacion').checked;

  if (!testimonio) {
    showMensajeTestimonio('Por favor escribe tu testimonio', 'error');
    return;
  }
  if (testimonio.length < 50) {
    showMensajeTestimonio('El testimonio debe tener al menos 50 caracteres', 'error');
    return;
  }
  if (!aceptarPublicacion) {
    showMensajeTestimonio('Debes aceptar que tu testimonio sea publicado', 'error');
    return;
  }

  // Deshabilitar botón y mostrar carga
  submitBtn.disabled = true;
  submitBtn.textContent = 'Enviando...';

  try {
    // Crear FormData para enviar archivos
    const formData = new FormData();
    formData.append('testimonio', testimonio);
    const imagenInput = document.getElementById('testimonioImagen');
    if (imagenInput && imagenInput.files.length > 0) {
      formData.append('imagen', imagenInput.files[0]);
    }
    // Enviar testimonio
    const response = await fetch('api/testimonios/enviar_testimonio.php', {
      method: 'POST',
      body: formData
    });
    const data = await response.json();
    if (data.success) {
      showMensajeTestimonio(data.message, 'success');
      form.reset();
      const charCount = document.querySelector('.char-count');
      if (charCount) {
        charCount.textContent = '0/500 caracteres';
        charCount.style.color = '#6b7280';
      }
    } else {
      showMensajeTestimonio(data.error || 'Error al enviar testimonio', 'error');
    }
  } catch (error) {
    console.error('Error enviando testimonio:', error);
    showMensajeTestimonio('Error de conexión', 'error');
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Enviar Testimonio';
  }
}

function showMensajeTestimonio(mensaje, tipo) {
  const mensajeDiv = document.getElementById('mensajeTestimonio');
  if (mensajeDiv) {
    mensajeDiv.textContent = mensaje;
    mensajeDiv.className = 'mensaje';
    mensajeDiv.style.display = 'block';
    if (tipo === 'success') {
      mensajeDiv.style.background = '#dcfce7';
      mensajeDiv.style.color = '#166534';
      mensajeDiv.style.border = '1px solid #bbf7d0';
    } else if (tipo === 'error') {
      mensajeDiv.style.background = '#fef2f2';
      mensajeDiv.style.color = '#dc2626';
      mensajeDiv.style.border = '1px solid #fecaca';
    } else {
      mensajeDiv.style.background = '#dbeafe';
      mensajeDiv.style.color = '#1e40af';
      mensajeDiv.style.border = '1px solid #bfdbfe';
    }
    setTimeout(() => {
      mensajeDiv.style.display = 'none';
    }, 5000);
  }
}

async function handleSubmitEditarLibro(event) {
  event.preventDefault();
  const form = event.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  
  // Obtener datos del formulario (solo categoría y descripción)
  const libroId = document.getElementById('editLibroId').value;
  const descripcion = document.getElementById('editDescripcion').value.trim();
  const categoria = document.getElementById('editCategoria').value;
  
  // Validaciones
  if (!descripcion || !categoria) {
    showError('La descripción y categoría son obligatorias');
    return;
  }
  
  if (descripcion.length < 10) {
    showError('La descripción debe tener al menos 10 caracteres');
    return;
  }
  
  // Deshabilitar botón y mostrar carga
  submitBtn.disabled = true;
  submitBtn.textContent = 'Guardando...';
  
  try {
    const response = await fetch('api/escritores/editar_libro.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        libro_id: libroId,
        descripcion: descripcion,
        categoria: categoria
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      showSuccess('Libro actualizado correctamente');
      cerrarModalEditar();
      // Recargar la pestaña de libros
      await loadLibrosTab();
    } else {
      showError(data.error || 'Error al actualizar el libro');
    }
  } catch (error) {
    console.error('Error actualizando libro:', error);
    showError('Error de conexión');
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = '💾 Guardar Cambios';
  }
} 