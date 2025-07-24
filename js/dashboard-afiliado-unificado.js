/**
 * Dashboard Afiliado - JavaScript Unificado
 * Combina funcionalidad original + nuevas características mejoradas
 * Versión: 2.0 - Unificada
 */

console.log('📁 dashboard-afiliado-unificado.js cargado');

// ========================================
// VARIABLES GLOBALES
// ========================================

let userData = null;
let dashboardData = null;
let analyticsData = null;
let notificacionesData = null;
let campanasData = null;
let charts = {};

// ========================================
// INICIALIZACIÓN PRINCIPAL
// ========================================

document.addEventListener('DOMContentLoaded', async function() {
    // Log visual de diagnóstico
    console.log('🚀 Dashboard Afiliado iniciando...');
    
    // Verificar autenticación
    if (!checkAuthentication()) {
        window.location.href = 'login.html';
        return;
    }

    // Cargar datos iniciales (esto ya incluye loadDashboardData)
    await loadUserData();
    
    // Verificar estado de activación
    checkActivationStatus();
    
    // Configurar pestañas
    setupTabs();
    
    // Configurar eventos
    setupEventListeners();
    
    // Cargar pestaña inicial
    loadInitialTab();
    
    // Iniciar actualizaciones automáticas
    startAutoRefresh();
    
    console.log('✅ Dashboard inicializado correctamente');
});

// ========================================
// FUNCIONES DE AUTENTICACIÓN Y DATOS
// ========================================

function checkAuthentication() {
    console.log('🔐 Verificando autenticación...');
    
    const userDataStr = localStorage.getItem('user_data');
    
    console.log('👤 User data encontrado:', userDataStr ? 'Sí' : 'No');
    
    if (!userDataStr) {
        console.log('❌ Faltan user_data');
        return false;
    }
    
    try {
        const user = JSON.parse(userDataStr);
        console.log('👤 Usuario parseado:', user);
        
        if (user.rol !== 'afiliado' && user.rol !== 'admin' && user.rol !== 'lector') {
            console.log('❌ Rol no válido:', user.rol);
            return false;
        }
        
        // Verificar si hay una sesión anterior diferente
        const currentSessionUser = sessionStorage.getItem('current_user_id');
        if (currentSessionUser && currentSessionUser !== user.id.toString()) {
            console.log('⚠️ Usuario diferente detectado, limpiando sesión anterior...');
            // Limpiar sesión anterior
            // localStorage.removeItem('session_token');
            // localStorage.removeItem('user_data');
            // sessionStorage.removeItem('current_user_id');
            return false;
        }
        
        // Guardar el ID del usuario actual en sessionStorage
        sessionStorage.setItem('current_user_id', user.id.toString());
        
        console.log('✅ Autenticación válida');
        return true;
    } catch (error) {
        console.error('❌ Error parseando user_data:', error);
        // localStorage.removeItem('session_token');
        // localStorage.removeItem('user_data');
        // sessionStorage.removeItem('current_user_id');
        return false;
    }
}

async function loadUserData() {
    console.log('🚀 loadUserData() INICIADA');
    try {
        console.log('🔄 Cargando datos del usuario...');
        
        // PRIMERO: Intentar cargar datos simulados si existen
        const simulatedData = localStorage.getItem('dashboard_data');
        if (simulatedData) {
            try {
                const data = JSON.parse(simulatedData);
                if (data.success) {
                    dashboardData = data;
                    userData = data.afiliado;
                    console.log('✅ Usando datos simulados del dashboard');
                    
                    // Actualizar la información del usuario
                    setTimeout(() => {
                        updateUserInfo();
                        updateDashboardStats();
                        console.log('✅ Información del usuario y estadísticas actualizadas con datos simulados');
                    }, 100);
                    
                    return; // Salir aquí si usamos datos simulados
                }
            } catch (e) {
                console.log('Error parseando datos simulados:', e);
            }
        }
        
        // SEGUNDO: Si no hay datos simulados, cargar del backend
        console.log('📡 No hay datos simulados, cargando del backend...');
        
        // Obtener datos del localStorage primero
        const userDataStr = localStorage.getItem('user_data');
        console.log('📋 user_data raw:', userDataStr);
        
        if (userDataStr) {
            try {
                userData = JSON.parse(userDataStr);
                console.log('👤 Datos del usuario parseados desde localStorage:', userData);
            } catch (parseError) {
                console.error('❌ Error parseando user_data:', parseError);
                userData = null;
            }
        } else {
            console.log('❌ No hay user_data en localStorage');
        }
        
        // Verificar sesión primero
        console.log('📡 Verificando sesión...');
        const sessionResponse = await fetch('api/auth/verificar_sesion.php', {
            method: 'GET',
            credentials: 'include'
        });
        
        const sessionData = await sessionResponse.json();
        console.log('📋 Datos de sesión:', sessionData);
        
        if (!sessionData.authenticated) {
            console.error('❌ No hay sesión activa');
            showError('No hay sesión activa. Por favor inicia sesión nuevamente.');
            return;
        }
        
        console.log('✅ Sesión verificada correctamente');
        
        console.log('📡 Haciendo petición a dashboard.php...');
        const response = await fetch('api/afiliados/dashboard.php', {
            method: 'GET',
            credentials: 'include'
        });
        
        console.log('📡 Respuesta del servidor:', response.status, response.statusText);
        
        const data = await response.json();
        console.log('📊 Datos del dashboard recibidos:', data);
        
        if (data.success) {
            // Actualizar datos del dashboard
            dashboardData = data;
            console.log('✅ Datos del dashboard cargados correctamente');
            
            // Actualizar información del usuario si no está disponible
            if (!userData && data.afiliado) {
                userData = data.afiliado;
                console.log('✅ userData actualizado desde dashboard');
            }
            
            // AHORA actualizar la información del usuario con todos los datos disponibles
            // Pequeño retraso para asegurar que el DOM esté listo
            setTimeout(() => {
                updateUserInfo();
                updateDashboardStats();
                console.log('✅ Información del usuario y estadísticas actualizadas');
            }, 100);
            
        } else {
            console.error('❌ Error en la respuesta del dashboard:', data);
            if (data.error === 'No autorizado') {
                // Redirigir al login si no está autorizado
                localStorage.removeItem('session_token');
                localStorage.removeItem('user_data');
                window.location.href = 'login.html';
            } else {
                showError('Error al cargar datos del dashboard. Usa debug-dashboard-simple.html para crear datos de prueba.');
            }
        }
    } catch (error) {
        console.error('❌ Error cargando datos del usuario:', error);
        showError('Error de conexión. Usa debug-dashboard-simple.html para crear datos de prueba.');
    }
    console.log('🏁 loadUserData() TERMINADA');
}

async function loadDashboardData() {
    try {
        console.log('📊 Cargando datos del dashboard...');
        
        // Verificar si la sesión existe
        const sessionCheck = await fetch('api/auth/check-session.php', {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        const sessionData = await sessionCheck.json();
        console.log('🔒 Estado de la sesión:', sessionData);
        
        if (!sessionData.success) {
            console.error('❌ Sesión no válida:', sessionData.error);
            showError('Por favor, inicia sesión nuevamente');
            return;
        }
        
        // Hacer la petición al dashboard
        const response = await fetch('api/afiliados/dashboard.php', {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        console.log('📊 Datos del dashboard recibidos:', data);
        
        if (data.success) {
            dashboardData = data;
            console.log('✅ Datos del dashboard cargados:', data);
            
            // Actualizar estadísticas del dashboard
            updateDashboardStats();
            
            // Actualizar información del usuario con los datos completos del dashboard
            updateUserInfo();
        } else {
            console.error('❌ Error en la respuesta del dashboard:', data.error);
            showError('Error al cargar los datos del dashboard');
        }
    } catch (error) {
        console.error('❌ Error en la petición:', error);
        showError('Error de conexión');
    }
}

// ========================================
// VERIFICACIÓN DE ACTIVACIÓN
// ========================================

function checkActivationStatus() {
    if (!userData) return;
    
    // Si el usuario está pendiente, mostrar alerta especial
    if (userData.estado === 'pendiente') {
        showPendingActivationAlert();
    }
}

// Modificar showPendingActivationAlert para que el mensaje se muestre siempre en la parte superior y no bloquee el resto del dashboard.
function showPendingActivationAlert() {
    // Crear alerta
    const alertContainer = document.createElement('div');
    alertContainer.className = 'pending-activation-alert';
    alertContainer.innerHTML = `
        <div class="alert-content">
            <div class="alert-icon">⚠️</div>
            <div class="alert-text">
                <h3>Tu cuenta está pendiente de activación</h3>
                <p>Para activar tu cuenta de afiliado, necesitas realizar tu primera compra en la tienda. Una vez que completes la compra, tu cuenta se activará automáticamente.</p>
            </div>
        </div>
    `;
    // Estilos de la alerta - más recatada y pequeña
    alertContainer.style.cssText = `
        position: relative;
        margin: 15px auto 0 auto;
        background: linear-gradient(135deg, #f39c12, #e67e22);
        color: white;
        padding: 12px 16px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        max-width: 500px;
        width: 95%;
        text-align: center;
        font-size: 0.9em;
        border-left: 4px solid #e74c3c;
    `;
    
    // Agregar estilos CSS para el contenido interno
    const style = document.createElement('style');
    style.textContent = `
        .pending-activation-alert .alert-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .pending-activation-alert .alert-icon {
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .pending-activation-alert .alert-text h3 {
            margin: 0 0 5px 0;
            font-size: 0.95rem;
            font-weight: 600;
        }
        
        .pending-activation-alert .alert-text p {
            margin: 0;
            line-height: 1.3;
            font-size: 0.85rem;
            opacity: 0.95;
        }
    `;
    document.head.appendChild(style);
    
    // Insertar alerta al inicio del contenido principal
    const mainContent = document.querySelector('main.content');
    if (mainContent) {
        mainContent.insertBefore(alertContainer, mainContent.firstChild);
    } else {
        document.body.insertBefore(alertContainer, document.body.firstChild);
    }
}

function irATienda() {
    // Cambiar a la pestaña de tienda
    const tiendaTab = document.querySelector('li[data-tab="tienda"]');
    if (tiendaTab) {
        tiendaTab.click();
    }
    
    // Cerrar la alerta
    cerrarAlerta();
}

function cerrarAlerta() {
    const alert = document.querySelector('.pending-activation-alert');
    if (alert) {
        alert.style.animation = 'slideDown 0.5s ease reverse';
        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 500);
    }
}

// ========================================
// FUNCIONES DE INTERFAZ
// ========================================

function setupTabs() {
    const tabButtons = document.querySelectorAll('nav li[data-tab]');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Remover clase activa de todos los botones y pestañas
            tabButtons.forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            
            // Activar botón y pestaña seleccionada
            this.classList.add('active');
            const targetTab = document.getElementById(tabId);
            if (targetTab) {
                targetTab.classList.add('active');
                loadTabContent(tabId);
            }
        });
    });
}

function setupEventListeners() {
    // Analytics
    document.getElementById('actualizarAnalytics')?.addEventListener('click', loadAnalyticsTab);
    document.getElementById('periodoAnalytics')?.addEventListener('change', loadAnalyticsTab);
    
    // Campañas
    document.getElementById('btnNuevaCampana')?.addEventListener('click', showModalCampana);
    document.getElementById('cerrarModalCampana')?.addEventListener('click', hideModalCampana);
    document.getElementById('cancelarCampana')?.addEventListener('click', hideModalCampana);
    document.getElementById('formCampana')?.addEventListener('submit', handleSubmitCampana);
    
    // Notificaciones
    document.getElementById('marcarTodasLeidas')?.addEventListener('click', marcarTodasNotificacionesLeidas);
    document.getElementById('actualizarNotificaciones')?.addEventListener('click', loadNotificacionesTab);
    document.getElementById('filtroNoLeidas')?.addEventListener('change', loadNotificacionesTab);
    
    // Configuración
    document.getElementById('formPerfil')?.addEventListener('submit', handleSubmitPerfil);
    document.getElementById('formNotificaciones')?.addEventListener('submit', handleSubmitNotificaciones);
    document.getElementById('formSeguridad')?.addEventListener('submit', handleSubmitSeguridad);
    document.getElementById('formPagos')?.addEventListener('submit', handleSubmitPagos);
}

function loadInitialTab() {
    console.log('🔄 Cargando pestaña inicial...');
    const activeTab = document.querySelector('nav li.active');
    if (activeTab) {
        const tabId = activeTab.getAttribute('data-tab');
        console.log('📋 Pestaña activa encontrada:', tabId);
        loadTabContent(tabId);
    } else {
        console.log('❌ No se encontró pestaña activa');
    }
}

async function loadTabContent(tabID) {
    console.log(`📊 Cargando pestaña: ${tabID}`);
    
    switch (tabID) {
        case 'inicio':
            await loadResumenTab();
            break;
        case 'analytics':
            await loadAnalyticsTab();
            break;
        case 'mi-red':
            await loadRedTab();
            break;
        case 'comisiones':
            await loadComisionesTab();
            break;
        case 'retiros':
            await loadRetirosTab();
            break;
        case 'campanas':
            await loadCampanasTab();
            break;
        case 'material':
            await loadMaterialTab();
            break;
        case 'tienda':
            await loadTiendaTab();
            break;
        case 'notificaciones':
            await loadNotificacionesTab();
            break;
        case 'mi-testimonio':
            await loadTestimonioTab();
            break;
        case 'configuracion':
            await loadConfiguracionTab();
            break;
    }
}

// ========================================
// PESTAÑA ANALYTICS (NUEVA)
// ========================================

async function loadAnalyticsTab() {
    try {
        showLoading('Cargando analytics...');
        
        const periodo = document.getElementById('periodoAnalytics').value;
        const response = await fetch(`api/afiliados/analytics.php?periodo=${periodo}`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            analyticsData = data;
            updateAnalyticsMetrics();
            createAnalyticsCharts();
            updateProductosTop();
        } else {
            showError('Error al cargar analytics');
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
    const conversion = analyticsData.metricas_conversion || {};
    const crecimiento = analyticsData.tendencias?.crecimiento || {};

    // Actualizar métricas principales
    updateElement('totalVentasAnalytics', metrics?.ventas?.total ?? 0);
    updateElement('volumenVentasAnalytics', formatCurrency(metrics?.ventas?.volumen ?? 0));
    updateElement('comisionesAnalytics', formatCurrency(metrics?.comisiones?.total_generadas ?? 0));
    updateElement('tasaConversion', (conversion.tasa_conversion ?? 0) + '%');

    // Actualizar indicadores de crecimiento
    updateCrecimientoElement('crecimientoVentas', crecimiento.ventas ?? 0);
    updateCrecimientoElement('crecimientoVolumen', crecimiento.volumen ?? 0);
}

function createAnalyticsCharts() {
    if (!analyticsData) return;
    
    // Gráfico de tendencias
    createTendenciasChart();
    
    // Gráfico de productos
    createProductosChart();
    
    // Gráfico de horarios
    createHorariosChart();
    
    // Gráfico de niveles
    createNivelesChart();
}

// Corregir createTendenciasChart para asegurar datos válidos
function createTendenciasChart() {
    const ctx = document.getElementById('chartTendencias');
    if (!ctx) return;
    
    if (charts.tendencias) {
        charts.tendencias.destroy();
    }
    
    const tendencias = analyticsData?.tendencias?.diarias || [];
    const labels = tendencias.map(t => formatDate(t.fecha));
    const ventas = tendencias.map(t => isNaN(Number(t.ventas)) ? 0 : Number(t.ventas));
    const comisiones = tendencias.map(t => isNaN(Number(t.comisiones)) ? 0 : Number(t.comisiones));
    
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
                    tension: 0.4
                },
                {
                    label: 'Comisiones',
                    data: comisiones,
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

function createProductosChart() {
    const ctx = document.getElementById('chartProductos');
    if (!ctx) return;
    
    if (charts.productos) {
        charts.productos.destroy();
    }
    
    const productos = analyticsData.productos_top.slice(0, 5);
    const labels = productos.map(p => p.titulo.substring(0, 20) + '...');
    const ventas = productos.map(p => p.veces_vendido);
    
    charts.productos = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: ventas,
                backgroundColor: [
                    '#5a67d8',
                    '#f59e0b',
                    '#10b981',
                    '#ef4444',
                    '#8b5cf6'
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

function createHorariosChart() {
    const ctx = document.getElementById('chartHorarios');
    if (!ctx) return;
    
    if (charts.horarios) {
        charts.horarios.destroy();
    }
    
    const horarios = analyticsData.analisis_horarios;
    const labels = horarios.map(h => h.hora + ':00');
    const ventas = horarios.map(h => h.ventas);
    
    charts.horarios = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Ventas por hora',
                data: ventas,
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

function createNivelesChart() {
    const ctx = document.getElementById('chartNiveles');
    if (!ctx) return;
    
    if (charts.niveles) {
        charts.niveles.destroy();
    }
    
    const niveles = analyticsData.analisis_red;
    const labels = niveles.map(n => 'Nivel ' + n.nivel);
    const afiliados = niveles.map(n => n.total_afiliados);
    const comisiones = niveles.map(n => n.comision_total_nivel);
    
    charts.niveles = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Afiliados',
                    data: afiliados,
                    backgroundColor: '#5a67d8',
                    yAxisID: 'y'
                },
                {
                    label: 'Comisiones',
                    data: comisiones,
                    backgroundColor: '#f59e0b',
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

function updateProductosTop() {
    if (!analyticsData || !analyticsData.productos_top) {
        console.log('❌ No hay datos de productos_top en analyticsData');
        return;
    }
    
    const tbody = document.getElementById('tablaProductosTop');
    if (!tbody) {
        console.log('❌ No se encontró el tbody #tablaProductosTop');
        return;
    }
    
    tbody.innerHTML = '';
    
    // Tomar solo los primeros 10 productos
    const productosTop10 = analyticsData.productos_top.slice(0, 10);
    
    if (productosTop10.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="empty-state">No hay datos de productos para el período seleccionado.</td></tr>';
        return;
    }
    
    productosTop10.forEach(producto => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="product-info">
                    <img src="images/${producto.imagen_portada || 'default-book.jpg'}" alt="${producto.titulo}" class="product-image" onerror="this.src='images/default-book.jpg'">
                    <div>
                        <strong>${producto.titulo}</strong>
                        <br>
                        <small>${producto.autor}</small>
                    </div>
                </div>
            </td>
            <td class="text-center">${producto.veces_vendido}</td>
            <td class="text-right">${formatCurrency(producto.volumen_generado)}</td>
            <td class="text-right">${formatCurrency(producto.comisiones_generadas)}</td>
            <td class="text-right">${formatCurrency(producto.precio_promedio)}</td>
        `;
        tbody.appendChild(row);
    });
}

// ========================================
// PESTAÑA CAMPAÑAS (NUEVA)
// ========================================

async function loadCampanasTab() {
    try {
        showLoading('Cargando campañas...');
        
        const response = await fetch('api/afiliados/campanas.php', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            campanasData = data.campanas;
            renderCampanas();
        } else {
            showError('Error al cargar campañas');
        }
    } catch (error) {
        console.error('Error cargando campañas:', error);
        showError('Error de conexión');
    } finally {
        hideLoading();
    }
}

function renderCampanas() {
    const container = document.getElementById('campanasGrid');
    if (!container) return;
    
    if (!campanasData || campanasData.length === 0) {
        container.innerHTML = `
            <div class="campana-empty">
                <h3>No tienes campañas creadas</h3>
                <p>Crea tu primera campaña para empezar a promocionar productos específicos.</p>
                <button onclick="showModalCampana()" class="btn btn-primary">Crear Primera Campaña</button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = campanasData.map(campana => `
        <div class="campana-card">
            <div class="campana-header">
                <h3>${campana.nombre}</h3>
                <span class="campana-status ${campana.estado}">${campana.estado}</span>
            </div>
            
            <div class="campana-content">
                <p>${campana.descripcion}</p>
                
                <div class="campana-stats">
                    <div class="stat">
                        <span class="stat-label">Ventas</span>
                        <span class="stat-value">${campana.ventas_generadas || 0}</span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Volumen</span>
                        <span class="stat-value">${formatCurrency(campana.volumen_generado || 0)}</span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Comisiones</span>
                        <span class="stat-value">${formatCurrency(campana.comisiones_generadas || 0)}</span>
                    </div>
                </div>
                
                <div class="campana-dates">
                    <small>Inicio: ${formatDate(campana.fecha_inicio)}</small>
                    ${campana.fecha_fin ? `<small>Fin: ${formatDate(campana.fecha_fin)}</small>` : ''}
                </div>
            </div>
            
            <div class="campana-actions">
                <button onclick="editarCampana(${campana.id})" class="btn btn-secondary">Editar</button>
                <button onclick="eliminarCampana(${campana.id})" class="btn btn-danger">Eliminar</button>
            </div>
        </div>
    `).join('');
}

function showModalCampana(campanaId = null) {
    const modal = document.getElementById('modalCampana');
    const titulo = document.getElementById('tituloModalCampana');
    const form = document.getElementById('formCampana');
    
    if (campanaId) {
        // Modo edición
        const campana = campanasData.find(c => c.id == campanaId);
        if (campana) {
            titulo.textContent = 'Editar Campaña';
            form.dataset.campanaId = campanaId;
            
            document.getElementById('nombreCampana').value = campana.nombre;
            document.getElementById('descripcionCampana').value = campana.descripcion;
            document.getElementById('objetivoVentas').value = campana.objetivo_ventas || '';
            document.getElementById('fechaInicio').value = campana.fecha_inicio;
            document.getElementById('fechaFin').value = campana.fecha_fin || '';
            document.getElementById('enlacePersonalizado').value = campana.enlace_personalizado || '';
        }
    } else {
        // Modo creación
        titulo.textContent = 'Nueva Campaña';
        form.dataset.campanaId = '';
        form.reset();
        document.getElementById('fechaInicio').value = new Date().toISOString().split('T')[0];
    }
    
    modal.style.display = 'block';
}

function hideModalCampana() {
    const modal = document.getElementById('modalCampana');
    modal.style.display = 'none';
}

async function handleSubmitCampana(event) {
    event.preventDefault();
    
    const formData = {
        nombre: document.getElementById('nombreCampana').value,
        descripcion: document.getElementById('descripcionCampana').value,
        objetivo_ventas: document.getElementById('objetivoVentas').value,
        fecha_inicio: document.getElementById('fechaInicio').value,
        fecha_fin: document.getElementById('fechaFin').value,
        enlace_personalizado: document.getElementById('enlacePersonalizado').value
    };
    
    const campanaId = event.target.dataset.campanaId;
    const method = campanaId ? 'PUT' : 'POST';
    const url = campanaId ? `api/afiliados/campanas.php?id=${campanaId}` : 'api/afiliados/campanas.php';
    
    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData),
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(campanaId ? 'Campaña actualizada' : 'Campaña creada');
            hideModalCampana();
            await loadCampanasTab();
        } else {
            showError(data.error || 'Error al guardar campaña');
        }
    } catch (error) {
        console.error('Error guardando campaña:', error);
        showError('Error de conexión');
    }
}

async function eliminarCampana(campanaId) {
    if (!confirm('¿Estás seguro de que quieres eliminar esta campaña?')) {
        return;
    }
    
    try {
        const response = await fetch(`api/afiliados/campanas.php?id=${campanaId}`, {
            method: 'DELETE',
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Campaña eliminada');
            await loadCampanasTab();
        } else {
            showError(data.error || 'Error al eliminar campaña');
        }
    } catch (error) {
        console.error('Error eliminando campaña:', error);
        showError('Error de conexión');
    }
}

// ========================================
// PESTAÑA NOTIFICACIONES (NUEVA)
// ========================================

async function loadNotificacionesTab() {
    try {
        const noLeidas = document.getElementById('filtroNoLeidas').checked;
        const url = `api/afiliados/notificaciones.php?no_leidas=${noLeidas}`;
        
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
            <div class="notificacion-empty">
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
                <h4>${notif.titulo_formateado}</h4>
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
        'comision': '💵',
        'retiro': '🏦',
        'nuevo_afiliado': '👥',
        'meta': '🎯',
        'sistema': '🔔'
    };
    return icons[tipo] || '📢';
}

function updateContadorNotificaciones() {
    const contador = document.getElementById('contadorNotificaciones');
    if (contador && notificacionesData) {
        contador.textContent = notificacionesData.no_leidas;
    }
}

async function marcarNotificacionLeida(notificacionId) {
    try {
        const response = await fetch('api/afiliados/notificaciones.php', {
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
        const response = await fetch('api/afiliados/notificaciones.php', {
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
// PESTAÑA MI TESTIMONIO
// ========================================

async function loadTestimonioTab() {
    console.log('📝 Cargando pestaña Mi Testimonio');
    
    // Configurar contador de caracteres
    const textarea = document.getElementById('testimonioTexto');
    const charCount = document.querySelector('.char-count');
    
    if (textarea && charCount) {
        textarea.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = `${length}/500 caracteres`;
            
            // Cambiar color según el límite
            charCount.classList.remove('near-limit', 'at-limit');
            if (length >= 450) {
                charCount.classList.add('near-limit');
            }
            if (length >= 500) {
                charCount.classList.add('at-limit');
            }
        });
    }
    
    // Configurar formulario
    const form = document.getElementById('formTestimonio');
    if (form) {
        form.addEventListener('submit', handleSubmitTestimonio);
    }
}

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
        if (imagenInput.files.length > 0) {
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
            document.querySelector('.char-count').textContent = '0/500 caracteres';
        } else {
            showMensajeTestimonio(data.error || 'Error al enviar testimonio', 'error');
        }
    } catch (error) {
        console.error('Error enviando testimonio:', error);
        showMensajeTestimonio('Error de conexión', 'error');
    } finally {
        // Restaurar botón
        submitBtn.disabled = false;
        submitBtn.textContent = 'Enviar Testimonio';
    }
}

function showMensajeTestimonio(mensaje, tipo) {
    const mensajeDiv = document.getElementById('mensajeTestimonio');
    if (mensajeDiv) {
        mensajeDiv.textContent = mensaje;
        mensajeDiv.className = `mensaje ${tipo}`;
        mensajeDiv.style.display = 'block';
        
        // Ocultar mensaje después de 5 segundos
        setTimeout(() => {
            mensajeDiv.style.display = 'none';
        }, 5000);
    }
}

// ========================================
// PESTAÑA CONFIGURACIÓN (NUEVA)
// ========================================

async function loadConfiguracionTab() {
    // Cargar datos del perfil
    if (userData) {
        document.getElementById('nombrePerfil').value = userData.nombre || '';
        document.getElementById('emailPerfil').value = userData.email || '';
        // Cargar otros campos si están disponibles
    }
    
    // Cargar configuración de notificaciones
    await loadConfiguracionNotificaciones();
}

async function loadConfiguracionNotificaciones() {
    try {
        const response = await fetch('api/afiliados/notificaciones.php', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success && data.configuracion) {
            const config = data.configuracion;
            
            document.getElementById('emailVentas').checked = config.email_ventas;
            document.getElementById('emailComisiones').checked = config.email_comisiones;
            document.getElementById('emailRetiros').checked = config.email_retiros;
            document.getElementById('emailNuevosAfiliados').checked = config.email_nuevos_afiliados;
            document.getElementById('pushVentas').checked = config.push_ventas;
            document.getElementById('pushComisiones').checked = config.push_comisiones;
            document.getElementById('pushRetiros').checked = config.push_retiros;
            document.getElementById('pushNuevosAfiliados').checked = config.push_nuevos_afiliados;
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
        telefono: document.getElementById('telefonoPerfil').value,
        pais: document.getElementById('paisPerfil').value
    };
    
    try {
        // Aquí iría la llamada al API para actualizar perfil
        showSuccess('Perfil actualizado correctamente');
    } catch (error) {
        console.error('Error actualizando perfil:', error);
        showError('Error al actualizar perfil');
    }
}

async function handleSubmitNotificaciones(event) {
    event.preventDefault();
    
    const configuracion = {
        email_ventas: document.getElementById('emailVentas').checked,
        email_comisiones: document.getElementById('emailComisiones').checked,
        email_retiros: document.getElementById('emailRetiros').checked,
        email_nuevos_afiliados: document.getElementById('emailNuevosAfiliados').checked,
        push_ventas: document.getElementById('pushVentas').checked,
        push_comisiones: document.getElementById('pushComisiones').checked,
        push_retiros: document.getElementById('pushRetiros').checked,
        push_nuevos_afiliados: document.getElementById('pushNuevosAfiliados').checked
    };
    
    try {
        const response = await fetch('api/afiliados/notificaciones.php', {
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
// FUNCIONES ORIGINALES (MANTENER COMPATIBILIDAD)
// ========================================

async function loadResumenTab() {
    console.log('🔄 Cargando pestaña de resumen...');
    
    // Cargar información completa del usuario
    if (userData) {
        console.log('👤 Datos del usuario disponibles, actualizando información...');
        updateUserInfo();
    } else {
        console.log('❌ No hay datos del usuario disponibles');
    }
    
    // Cargar estadísticas del dashboard
    if (dashboardData) {
        console.log('📊 Datos del dashboard disponibles, actualizando estadísticas...');
        updateDashboardStats();
    } else {
        console.log('❌ No hay datos del dashboard disponibles');
    }
    
    // Cargar actividad reciente
    if (dashboardData && dashboardData.datos_recientes) {
        console.log('📈 Datos recientes disponibles, actualizando actividad...');
        updateActividadReciente();
    } else {
        console.log('❌ No hay datos recientes disponibles');
    }
    
    // Crear gráfico de tendencias si hay datos
    if (dashboardData && dashboardData.datos_graficos) {
        console.log('📊 Datos de gráficos disponibles, creando gráfico...');
        createTendenciasChart();
    } else {
        console.log('❌ No hay datos de gráficos disponibles');
    }
    
    console.log('✅ Pestaña de resumen cargada');
}

function updateActividadReciente() {
    if (!dashboardData || !dashboardData.datos_recientes) return;
    
    const container = document.getElementById('actividadReciente');
    if (!container) return;
    
    const actividades = [];
    
    // Agregar ventas recientes
    if (dashboardData.datos_recientes.ventas) {
        dashboardData.datos_recientes.ventas.forEach(venta => {
            actividades.push({
                tipo: 'venta',
                mensaje: `Nueva venta: ${venta.libro_titulo}`,
                monto: isNaN(Number(venta.precio)) ? 0 : Number(venta.precio),
                fecha: venta.fecha_venta,
                icono: '💰'
            });
        });
    }
    
    // Agregar comisiones recientes
    if (dashboardData.datos_recientes.comisiones) {
        dashboardData.datos_recientes.comisiones.forEach(comision => {
            actividades.push({
                tipo: 'comision',
                mensaje: `Comisión generada: Nivel ${comision.nivel}`,
                monto: isNaN(Number(comision.monto)) ? 0 : Number(comision.monto),
                fecha: comision.fecha_generacion,
                icono: '💵'
            });
        });
    }
    
    // Agregar retiros recientes
    if (dashboardData.datos_recientes.retiros) {
        dashboardData.datos_recientes.retiros.forEach(retiro => {
            actividades.push({
                tipo: 'retiro',
                mensaje: `Retiro solicitado: ${retiro.metodo}`,
                monto: isNaN(Number(retiro.monto)) ? 0 : Number(retiro.monto),
                fecha: retiro.fecha_solicitud,
                icono: '🏦'
            });
        });
    }
    
    // Ordenar por fecha (más recientes primero)
    actividades.sort((a, b) => new Date(b.fecha) - new Date(a.fecha));
    
    // Mostrar solo las 5 más recientes
    const actividadesRecientes = actividades.slice(0, 5);
    
    if (actividadesRecientes.length === 0) {
        container.innerHTML = '<p class="empty-state">No hay actividad reciente</p>';
        return;
    }
    
    container.innerHTML = actividadesRecientes.map(actividad => `
        <div class="actividad-item">
            <div class="actividad-icon">${actividad.icono}</div>
            <div class="actividad-content">
                <h4>${actividad.mensaje}</h4>
                <p>${formatCurrency(isNaN(actividad.monto) ? 0 : actividad.monto)}</p>
                <small>${formatDate(actividad.fecha)}</small>
            </div>
        </div>
    `).join('');
}

async function loadRedTab() {
    // Implementación original de la red
    const container = document.getElementById('accordionRed');
    if (container) {
        container.innerHTML = '<p>Cargando red multinivel...</p>';
        // Aquí iría la lógica original de carga de red
    }
}

async function loadComisionesTab() {
    // Implementación original de comisiones
    if (dashboardData && dashboardData.datos_recientes) {
        const comisiones = dashboardData.datos_recientes.comisiones;
        const tbody = document.getElementById('tablaComisionesBody');
        if (tbody) {
            tbody.innerHTML = comisiones.map(comision => `
                <tr>
                    <td>Nivel ${comision.nivel}</td>
                    <td>${formatCurrency(comision.monto)}</td>
                    <td>${comision.estado}</td>
                    <td>${formatDate(comision.fecha_generacion)}</td>
                </tr>
            `).join('');
        }
    }
}

async function loadRetirosTab() {
    // Implementación original de retiros
    if (dashboardData && dashboardData.datos_recientes) {
        const retiros = dashboardData.datos_recientes.retiros;
        const tbody = document.getElementById('tablaRetirosBody');
        if (tbody) {
            tbody.innerHTML = retiros.map(retiro => `
                <tr>
                    <td>${formatCurrency(retiro.monto)}</td>
                    <td>${retiro.metodo}</td>
                    <td>${retiro.estado}</td>
                    <td>${formatDate(retiro.fecha_solicitud)}</td>
                </tr>
            `).join('');
        }
    }
}

async function loadMaterialTab() {
    // Implementación original de material
    if (userData) {
        const enlaceElement = document.getElementById('linkAfiliado');
        if (enlaceElement) {
            enlaceElement.value = userData.enlace_afiliado;
        }
        
        const qrElement = document.getElementById('qrAfiliadoMaterial');
        if (qrElement) {
            qrElement.src = userData.qr_code;
        }
    }
}

async function loadTiendaTab() {
    console.log('🛒 Cargando tienda en el dashboard...');
    
    try {
        const response = await fetch('api/libros/disponibles.php', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            const contenedorLibros = document.getElementById('contenedorLibros');
            if (contenedorLibros) {
                contenedorLibros.innerHTML = data.libros.map(libro => `
                    <div class="libro-card" onclick="mostrarModalLibro(${JSON.stringify(libro).replace(/"/g, '&quot;')})">
                        <img src="images/${libro.imagen_portada || 'default-book.jpg'}" alt="${libro.titulo}" onerror="this.src='images/default-book.jpg'">
                        <h4>${libro.titulo}</h4>
                        <p>${libro.descripcion}</p>
                        <div class="libro-precio">
                            <span class="precio-original">$${libro.precio ? parseFloat(libro.precio).toLocaleString() : '0'}</span>
                            <span class="precio-afiliado">$${libro.precio_afiliado ? parseFloat(libro.precio_afiliado).toLocaleString() : '0'}</span>
                        </div>
                    </div>
                `).join('');
                console.log('✅ Libros cargados en el dashboard');
            }
        } else {
            console.error('❌ Error cargando libros:', data.error);
            showError('Error cargando libros');
        }
    } catch (error) {
        console.error('❌ Error cargando tienda:', error);
        showError('Error de conexión');
    }
}

function mostrarModalLibro(libro) {
    console.log('📖 Mostrando modal del libro:', libro.titulo);
    
    const modal = document.getElementById('detalleLibro');
    if (!modal) {
        console.error('❌ Modal no encontrado');
        return;
    }
    
    // Imagen grande del libro
    const portadaElement = modal.querySelector('.modal-portada');
    if (portadaElement) {
        portadaElement.src = `images/${libro.imagen_portada || 'default-book.jpg'}`;
        portadaElement.onerror = function() {
            this.src = 'images/default-book.jpg';
        };
    }
    
    // Precio
    const precioElement = modal.querySelector('.modal-precio');
    if (precioElement) {
        console.log('💰 Datos del precio en el modal:');
        console.log('  - libro.precio_afiliado:', libro.precio_afiliado);
        console.log('  - Tipo:', typeof libro.precio_afiliado);
        console.log('  - libro.precio:', libro.precio);
        
        let precioFormateado = '0';
        
        if (libro.precio_afiliado && libro.precio_afiliado !== null && libro.precio_afiliado !== undefined) {
            precioFormateado = parseFloat(libro.precio_afiliado).toLocaleString();
        } else if (libro.precio && libro.precio !== null && libro.precio !== undefined) {
            console.log('⚠️ Usando precio normal en lugar de precio_afiliado');
            precioFormateado = parseFloat(libro.precio).toLocaleString();
        }
        
        console.log('💰 Precio formateado final:', precioFormateado);
        precioElement.textContent = `$${precioFormateado}`;
    }
    
    // Título y descripción
    const tituloElement = modal.querySelector('.modal-titulo');
    if (tituloElement) {
        tituloElement.textContent = libro.titulo;
    }
    
    const descripcionElement = modal.querySelector('.modal-descripcion');
    if (descripcionElement) {
        descripcionElement.textContent = libro.descripcion;
    }
    
    // Foto y nombre del autor
    const autorFotoElement = modal.querySelector('.modal-autor-foto');
    if (autorFotoElement) {
        autorFotoElement.src = `images/${libro.autor_foto || 'default-author.jpg'}`;
        autorFotoElement.onerror = function() {
            this.src = 'images/default-author.jpg';
        };
    }
    
    const autorNombreElement = modal.querySelector('.modal-autor-nombre');
    if (autorNombreElement) {
        autorNombreElement.innerHTML = `<strong>${libro.autor_nombre}</strong>`;
    }
    
    // Bio del autor
    const autorBioElement = modal.querySelector('.modal-autor-bio');
    if (autorBioElement) {
        autorBioElement.textContent = libro.autor_bio || 'Biografía no disponible';
    }
    
    // Configurar botón de compra
    const comprarBtn = modal.querySelector('#comprarLibroDashboardBtn');
    if (comprarBtn) {
        console.log('🛒 Configurando botón de compra para libro ID:', libro.id);
        comprarBtn.onclick = function() {
            console.log('🛒 Botón de compra clickeado para libro ID:', libro.id);
            comprarLibro(libro.id);
        };
    } else {
        console.error('❌ Botón de compra no encontrado en el modal');
    }
    
    // Mostrar modal
    modal.style.display = 'block';
    
    // Configurar cierre del modal
    const cerrarBtn = modal.querySelector('#cerrarDetalle');
    if (cerrarBtn) {
        cerrarBtn.onclick = function() {
            modal.style.display = 'none';
        };
    }
    
    // Cerrar al hacer clic fuera del modal
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };
    
    console.log('✅ Modal del libro mostrado correctamente');
}

// ========================================
// FUNCIONES AUXILIARES
// ========================================

function updateUserInfo() {
    console.log('🔄 updateUserInfo() iniciada');
    console.log('👤 userData disponible:', userData);
    
    if (!userData) {
        console.log('❌ No hay datos del usuario disponibles');
        return;
    }

    console.log('👤 Actualizando información del usuario:', userData);

    // Actualizar saludo principal
    const saludoElement = document.querySelector('h1');
    console.log('📝 Elemento h1 encontrado:', saludoElement ? 'Sí' : 'No');
    if (saludoElement) {
        saludoElement.textContent = `Bienvenido, ${userData.nombre}`;
        console.log('✅ Saludo actualizado:', saludoElement.textContent);
    }

    // Información básica del afiliado
    console.log('📝 Actualizando elementos básicos...');
    
    // ID de afiliado - usar datos del dashboard si están disponibles
    const afiliadoId = dashboardData?.afiliado?.codigo_afiliado ?? userData.codigo_afiliado ?? 'N/A';
    updateElement('afiliadoId', afiliadoId);
    
    updateElement('afiliadoNombre', userData.nombre ?? 'N/A');
    updateElement('emailAfiliado', userData.email ?? 'N/A');
    
    // Fecha de registro - usar datos del dashboard si están disponibles
    const fechaRegistro = dashboardData?.afiliado?.fecha_registro ?? userData.fecha_registro;
    updateElement('fechaRegistro', fechaRegistro ? formatDate(fechaRegistro) : 'N/A');
    
    // Nivel - usar datos del dashboard si están disponibles
    const nivel = dashboardData?.afiliado?.nivel ?? userData.nivel;
    updateElement('nivelAfiliado', nivel !== undefined && nivel !== null ? `Nivel ${nivel}` : 'N/A');

    // Estado de activación
    const estadoElement = document.getElementById('estadoActivacion');
    console.log('📝 Elemento estadoActivacion encontrado:', estadoElement ? 'Sí' : 'No');
    if (estadoElement) {
        const estado = userData.estado ?? 'pendiente';
        const estadoText = estado === 'activo' ? 'Activo' : 
                          estado === 'pendiente' ? 'Pendiente' : 
                          estado === 'inactivo' ? 'Inactivo' : 'Desconocido';
        estadoElement.textContent = estadoText;
        estadoElement.className = `estado-badge ${estado}`;
        console.log('✅ Estado actualizado:', estadoText);
    }

    // Información adicional del afiliado
    console.log('📊 Procesando información adicional del afiliado...');
    
    // Usar datos del dashboard si están disponibles, sino usar datos básicos
    const afiliadoData = dashboardData?.afiliado ?? {};
    
    // Frontal asignado
    const frontal = afiliadoData.frontal !== undefined && afiliadoData.frontal !== null ? (afiliadoData.frontal ? 'Sí' : 'No') : 'N/A';
    updateElement('frontalAsignado', frontal);
    
    // Patrocinador
    const patrocinador = afiliadoData.nombre_patrocinador ?? 'Sin patrocinador';
    updateElement('nombrePatrocinador', patrocinador);

    // Enlace de afiliado
    const enlaceElement = document.getElementById('enlaceAfiliado');
    console.log('📝 Elemento enlaceAfiliado encontrado:', enlaceElement ? 'Sí' : 'No');
    if (enlaceElement) {
        let enlace = afiliadoData.enlace_afiliado;
        
        // Si no hay enlace en los datos del dashboard, generarlo con el código de afiliado
        if (!enlace && afiliadoId && afiliadoId !== 'N/A') {
            enlace = `http://localhost/publiery/registro.html?ref=${afiliadoId}`;
        } else if (!enlace) {
            enlace = 'N/A';
        }
        
        enlaceElement.href = enlace;
        enlaceElement.textContent = enlace;
        console.log('✅ Enlace actualizado:', enlace);
    }

    // Código QR
    const qrElement = document.getElementById('codigoQR');
    console.log('📝 Elemento codigoQR encontrado:', qrElement ? 'Sí' : 'No');
    if (qrElement) {
        let qrUrl = afiliadoData.qr_code;
        
        // Si no hay QR en los datos del dashboard, generarlo con el enlace de afiliado
        if (!qrUrl && afiliadoId && afiliadoId !== 'N/A') {
            const enlaceQR = `http://localhost/publiery/registro.html?ref=${afiliadoId}`;
            qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(enlaceQR)}`;
        }
        
        if (qrUrl && qrUrl !== 'N/A') {
            qrElement.src = qrUrl;
            qrElement.style.display = 'block';
            console.log('✅ QR actualizado con URL:', qrUrl);
        } else {
            qrElement.style.display = 'none';
            console.log('❌ QR no disponible');
        }
    }

    console.log('✅ Información del usuario actualizada completamente');
}

function updateDashboardStats() {
    if (!dashboardData) return;
    
    const stats = dashboardData.estadisticas;
    
    // Actualizar estadísticas principales
    updateElement('totalAfiliados', stats.total_afiliados_red || 0);
    updateElement('totalVentas', dashboardData.datos_recientes.ventas?.length || 0);
    updateElement('totalComisiones', formatCurrency(stats.comisiones.total_ganado || 0));
    updateElement('saldoDisponible', formatCurrency(stats.comisiones.total_ganado - stats.comisiones.comisiones_pagadas || 0));
    
    // Actualizar indicadores de crecimiento (por ahora en 0%)
    updateElement('crecimientoAfiliados', '+0%');
    updateElement('crecimientoVentas', '+0%');
    updateElement('crecimientoComisiones', '+0%');
}

function updateCrecimientoElement(elementId, valor) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const signo = valor >= 0 ? '+' : '';
    const color = valor >= 0 ? '#10b981' : '#ef4444';
    
    element.textContent = `${signo}${valor}%`;
    element.style.color = color;
}

function updateElement(id, value) {
    console.log(`🔍 Buscando elemento con ID: ${id}`);
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
        console.log(`✅ Elemento ${id} actualizado con: ${value}`);
    } else {
        console.log(`❌ Elemento ${id} NO encontrado en el DOM`);
        console.log(`🔍 Elementos disponibles en el DOM:`);
        // Listar todos los elementos con ID para debug
        const allElements = document.querySelectorAll('[id]');
        allElements.forEach(el => {
            if (el.id.includes('afiliado') || el.id.includes('email') || el.id.includes('fecha') || 
                el.id.includes('nivel') || el.id.includes('estado') || el.id.includes('frontal') || 
                el.id.includes('patrocinador') || el.id.includes('enlace') || el.id.includes('qr')) {
                console.log(`  - ${el.id}: ${el.tagName}`);
            }
        });
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
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function showLoading(message = 'Cargando...') {
    // Implementar loading
    console.log('Loading:', message);
}

function hideLoading() {
    // Ocultar loading
    console.log('Loading hidden');
}

function showError(message) {
    // Implementar notificación de error
    console.error('Error:', message);
    alert('Error: ' + message);
}

function showSuccess(message) {
    // Implementar notificación de éxito
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

// ========================================
// FUNCIONES DE UTILIDAD ORIGINALES
// ========================================

function copiarEnlace(elementId) {
    console.log('📋 Intentando copiar enlace del elemento:', elementId);
    
    const element = document.getElementById(elementId);
    if (!element) {
        console.error('❌ Elemento no encontrado:', elementId);
        showError('Elemento no encontrado');
        return;
    }
    
    // Si es un enlace (a), copiar el href
    if (element.tagName === 'A') {
        const enlace = element.href;
        console.log('📋 Copiando enlace:', enlace);
        
        // Usar la API moderna de clipboard si está disponible
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(enlace).then(() => {
                showSuccess('Enlace copiado al portapapeles');
                console.log('✅ Enlace copiado exitosamente');
            }).catch(err => {
                console.error('❌ Error copiando con clipboard API:', err);
                // Fallback al método antiguo
                fallbackCopyTextToClipboard(enlace);
            });
        } else {
            // Fallback para navegadores antiguos
            fallbackCopyTextToClipboard(enlace);
        }
    } else {
        // Si es un input, usar el método tradicional
        element.select();
        element.setSelectionRange(0, 99999); // Para móviles
        try {
            document.execCommand('copy');
            showSuccess('Enlace copiado al portapapeles');
            console.log('✅ Enlace copiado exitosamente');
        } catch (err) {
            console.error('❌ Error copiando:', err);
            showError('Error al copiar el enlace');
        }
    }
}

function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showSuccess('Enlace copiado al portapapeles');
        console.log('✅ Enlace copiado exitosamente (fallback)');
    } catch (err) {
        console.error('❌ Error copiando con fallback:', err);
        showError('Error al copiar el enlace');
    }
    
    document.body.removeChild(textArea);
}

function logout() {
    console.log('🚪 Cerrando sesión...');
    
    // Limpiar localStorage
    localStorage.removeItem('session_token');
    localStorage.removeItem('user_data');
    localStorage.removeItem('dashboard_data');
    localStorage.removeItem('afiliado_data');
    localStorage.removeItem('afiliado_token');
    // Limpiar todo localStorage si es seguro:
    // localStorage.clear(); // (opcional, si solo se usa para sesión)

    // Limpiar sessionStorage
    sessionStorage.removeItem('current_user_id');
    sessionStorage.removeItem('afiliado_id');
    sessionStorage.removeItem('afiliado_token');
    // sessionStorage.clear(); // (opcional, si solo se usa para sesión)

    // Limpiar variables globales
    userData = null;
    dashboardData = null;
    analyticsData = null;
    notificacionesData = null;
    campanasData = null;
    charts = {};

    // Hacer logout en el servidor
    fetch('api/auth/logout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    }).catch(error => {
        console.log('Error en logout del servidor:', error);
    }).finally(() => {
        // Redirigir al login con parámetro para forzar limpieza
        window.location.href = 'login.html?logout=true';
    });
}

function solicitarRetiro() {
    // Implementación original de solicitud de retiro
}

function comprarLibro(libroId) {
    console.log('🛒 Comprando libro ID:', libroId);
    // Redirigir a la página de pago con el libro seleccionado
    window.location.href = `pago.html?libro=${libroId}`;
}

function descargarQR() {
    console.log('📥 Intentando descargar código QR...');
    
    const qrImage = document.getElementById('codigoQR');
    if (!qrImage) {
        console.error('❌ Elemento QR no encontrado');
        showError('Código QR no encontrado');
        return;
    }
    
    if (!qrImage.src || qrImage.src === '' || qrImage.style.display === 'none') {
        console.error('❌ Código QR no disponible');
        showError('Código QR no disponible');
        return;
    }
    
    console.log('📥 Descargando QR desde:', qrImage.src);
    
    // Convertir la imagen a blob para descarga
    fetch(qrImage.src)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al obtener la imagen QR');
            }
            return response.blob();
        })
        .then(blob => {
            // Crear URL del blob
            const blobUrl = window.URL.createObjectURL(blob);
            
            // Crear enlace de descarga
            const link = document.createElement('a');
            link.href = blobUrl;
            link.download = `qr-afiliado-${userData?.codigo_afiliado || 'user'}.png`;
            link.style.display = 'none';
            
            // Agregar al DOM y hacer clic
            document.body.appendChild(link);
            link.click();
            
            // Limpiar
            document.body.removeChild(link);
            window.URL.revokeObjectURL(blobUrl);
            
            showSuccess('Código QR descargado exitosamente');
            console.log('✅ Código QR descargado exitosamente');
        })
        .catch(error => {
            console.error('❌ Error descargando QR:', error);
            showError('Error al descargar el código QR');
        });
}

// ========================================
// EXPORTAR FUNCIONES PARA USO GLOBAL
// ========================================

// Función de prueba para verificar que las funciones están disponibles
function testFunciones() {
    console.log('🧪 Probando funciones...');
    console.log('📋 copiarEnlace disponible:', typeof window.copiarEnlace);
    console.log('📥 descargarQR disponible:', typeof window.descargarQR);
    console.log('🚪 logout disponible:', typeof window.logout);
}

// Ejecutar test al cargar
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(testFunciones, 1000);
});

window.loadAnalyticsTab = loadAnalyticsTab;
window.showModalCampana = showModalCampana;
window.hideModalCampana = hideModalCampana;
window.editarCampana = showModalCampana;
window.eliminarCampana = eliminarCampana;
window.irATienda = irATienda;
window.cerrarAlerta = cerrarAlerta;
// Exportar funciones globalmente
window.copiarEnlace = copiarEnlace;
window.descargarQR = descargarQR;
window.mostrarModalLibro = mostrarModalLibro;
window.comprarLibro = comprarLibro;
window.logout = logout;
window.fallbackCopyTextToClipboard = fallbackCopyTextToClipboard;

console.log('🌐 Funciones exportadas globalmente:');
console.log('  - copiarEnlace:', typeof window.copiarEnlace);
console.log('  - descargarQR:', typeof window.descargarQR);
console.log('  - logout:', typeof window.logout); 