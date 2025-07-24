/**
 * Dashboard Afiliado - JavaScript Actualizado
 * Integración con API PHP y base de datos MySQL
 */

// Variables globales
let userData = null;
let dashboardData = null;

// Inicialización al cargar la página
function checkAuthentication() {
    const token = localStorage.getItem('session_token');
    const userData = localStorage.getItem('user_data');
    if (!token || !userData) {
        limpiarSesion();
        return false;
    }
    try {
        const user = JSON.parse(userData);
        if (user.rol !== 'afiliado' && user.rol !== 'admin') {
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
    if (!checkAuthentication()) {
        window.location.href = 'login.html';
        return;
    }

    console.log('🚀 Dashboard afiliado cargando...');
    
    // Verificar autenticación
    const token = localStorage.getItem('session_token');
    const userData = localStorage.getItem('user_data');
    
    console.log('Token:', token ? 'Presente' : 'Ausente');
    console.log('User data:', userData ? JSON.parse(userData) : 'Ausente');
    
    if (!token || !userData) {
        console.log('❌ No hay token o user_data, redirigiendo a login');
        window.location.href = 'login.html';
        return;
    }

    console.log('✅ Autenticación verificada, cargando datos...');
    
    // Cargar datos del usuario
    await loadUserData();
    
    // Verificar estado de activación
    checkActivationStatus();
    
    // Cargar datos del dashboard
    await loadDashboardData();
    
    // Configurar pestañas
    setupTabs();
    
    // Cargar pestaña inicial
    loadInitialTab();
    
    console.log('✅ Dashboard cargado completamente');
});

// Verificar estado de activación del afiliado
function checkActivationStatus() {
    if (!userData) return;
    
    // Si el usuario está pendiente, mostrar alerta especial
    if (userData.estado === 'pendiente') {
        showPendingActivationAlert();
    }
}

// Mostrar alerta para afiliados pendientes
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
                <div class="alert-actions">
                    <button onclick="irATienda()" class="btn-primary">Ir a la Tienda</button>
                    <button onclick="cerrarAlerta()" class="btn-secondary">Entendido</button>
                </div>
            </div>
        </div>
    `;
    
    // Estilos de la alerta
    alertContainer.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
        color: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        z-index: 1000;
        max-width: 500px;
        width: 90%;
        animation: slideDown 0.5s ease;
    `;
    
    // Agregar estilos CSS
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideDown {
            from { transform: translateX(-50%) translateY(-100%); opacity: 0; }
            to { transform: translateX(-50%) translateY(0); opacity: 1; }
        }
        
        .pending-activation-alert .alert-content {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .pending-activation-alert .alert-icon {
            font-size: 2rem;
            flex-shrink: 0;
        }
        
        .pending-activation-alert .alert-text h3 {
            margin: 0 0 10px 0;
            font-size: 1.2rem;
        }
        
        .pending-activation-alert .alert-text p {
            margin: 0 0 15px 0;
            line-height: 1.5;
        }
        
        .pending-activation-alert .alert-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .pending-activation-alert .btn-primary {
            background: #27ae60;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .pending-activation-alert .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .pending-activation-alert .btn-primary:hover {
            background: #219a52;
        }
        
        .pending-activation-alert .btn-secondary:hover {
            background: rgba(255,255,255,0.3);
        }
    `;
    document.head.appendChild(style);
    
    // Agregar al DOM
    document.body.appendChild(alertContainer);
    
    // Hacer scroll hacia arriba para mostrar la alerta
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Función para ir a la tienda
function irATienda() {
    // Cambiar a la pestaña de tienda
    const tiendaTab = document.querySelector('li[data-tab="tienda"]');
    if (tiendaTab) {
        tiendaTab.click();
    }
    
    // Cerrar la alerta
    cerrarAlerta();
}

// Función para cerrar la alerta
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

// Cargar datos del usuario
async function loadUserData() {
    try {
        const userDataStr = localStorage.getItem('user_data');
        userData = JSON.parse(userDataStr);
        
        // Actualizar información en el header
        updateUserInfo();
        
    } catch (error) {
        // Log solo en desarrollo
        if (typeof console !== 'undefined' && console.error) {
            console.error('Error cargando datos del usuario:', error);
        }
        showError('Error cargando datos del usuario');
    }
}

// Cargar datos del dashboard desde la API
async function loadDashboardData() {
    try {
        console.log('📊 Cargando datos del dashboard...');
        showLoading('Cargando dashboard...');
        
        const response = await fetch('api/afiliados/dashboard.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('session_token')}`
            }
        });

        console.log('📡 Respuesta del servidor:', response.status, response.statusText);

        if (!response.ok) {
            if (response.status === 401) {
                console.log('❌ Sesión expirada, redirigiendo a login');
                // Sesión expirada
                localStorage.removeItem('session_token');
                localStorage.removeItem('user_data');
                window.location.href = 'login.html';
                return;
            }
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        console.log('📋 Datos recibidos:', data);
        
        if (data.success) {
            console.log('✅ Datos cargados exitosamente');
            dashboardData = data;
            updateDashboardStats();
            const afiliado = data.afiliado;
            document.getElementById('frontalAsignado').textContent = afiliado.frontal;
            document.getElementById('nombrePatrocinador').textContent = afiliado.nombre_patrocinador;
            document.getElementById('enlaceAfiliado').textContent = afiliado.enlace_afiliado;
            document.getElementById('enlaceAfiliado').href = afiliado.enlace_afiliado;
            document.getElementById('codigoQR').src = afiliado.qr_code;
        } else {
            console.log('❌ Error en la respuesta:', data.error);
            throw new Error(data.error || 'Error cargando datos');
        }

    } catch (error) {
        console.error('❌ Error cargando dashboard:', error);
        // Log solo en desarrollo
        if (typeof console !== 'undefined' && console.error) {
            console.error('Error cargando dashboard:', error);
        }
        showError('Error cargando datos del dashboard');
    } finally {
        hideLoading();
    }
}

// Actualizar información del usuario en el header
function updateUserInfo() {
    if (!userData) return;
    
    const userNameElement = document.querySelector('.user-name');
    const userEmailElement = document.querySelector('.user-email');
    const userCodeElement = document.querySelector('.user-code');
    
    if (userNameElement) userNameElement.textContent = userData.nombre;
    if (userEmailElement) userEmailElement.textContent = userData.email;
    if (userCodeElement) userCodeElement.textContent = userData.codigo_afiliado || 'N/A';
}

// Actualizar estadísticas del dashboard
function updateDashboardStats() {
    if (!dashboardData) return;
    const afiliado = dashboardData.afiliado;
    // Mostrar nombre en el saludo principal
    const saludo = document.querySelector('h1');
    if (saludo && afiliado && afiliado.nombre) {
        saludo.textContent = `Bienvenido, ${afiliado.nombre}`;
    }
    // Datos de afiliado
    if (afiliado) {
        updateElement('afiliadoId', afiliado.id);
        updateElement('afiliadoNombre', afiliado.nombre);
        updateElement('frontalAfiliado', afiliado.frontal);
        updateElement('enlaceAfiliado', afiliado.enlace_afiliado || '');
        // Estado de activación
        updateElement('estadoActivacionTexto', afiliado.estado);
        updateElement('fechaActivacion', afiliado.fecha_activacion ? afiliado.fecha_activacion : 'Pendiente');
    }
    const stats = dashboardData.estadisticas;
    
    // Actualizar estadísticas principales
    updateStatElement('total-comisiones', stats.comisiones.total_comisiones || 0);
    updateStatElement('comisiones-pendientes', formatCurrency(stats.comisiones.comisiones_pendientes || 0));
    updateStatElement('comisiones-pagadas', formatCurrency(stats.comisiones.comisiones_pagadas || 0));
    updateStatElement('total-ganado', formatCurrency(stats.comisiones.total_ganado || 0));
    updateStatElement('total-afiliados', stats.total_afiliados_red || 0);
    
    // Actualizar información del afiliado
    if (afiliado) {
        updateElement('codigo-afiliado', afiliado.codigo_afiliado);
        updateElement('nivel-afiliado', `Nivel ${afiliado.nivel}`);
        updateElement('enlace-afiliado', afiliado.enlace_afiliado);
        updateElement('fecha-activacion', formatDate(afiliado.fecha_activacion));
    }

    updateElement('totalAfiliados', stats.total_afiliados_red);
    updateElement('totalVentas', stats.total_ventas || 0);
    updateElement('totalComisiones', '$' + (stats.comisiones.total_ganado || 0).toLocaleString('es-CO'));
}

// Configurar sistema de pestañas
function setupTabs() {
    document.querySelectorAll(".sidebar nav li[data-tab]").forEach(item => {
        item.addEventListener("click", async () => {
            const tabID = item.dataset.tab;
            
            // Cambiar pestaña activa
            document.querySelectorAll(".sidebar nav li").forEach(el => el.classList.remove("active"));
            document.querySelectorAll(".tab").forEach(tab => tab.classList.remove("active"));
            
            item.classList.add("active");
            document.getElementById(tabID).classList.add("active");
            
            // Cargar contenido según la pestaña
            await loadTabContent(tabID);
        });
    });
}

// Cargar contenido de pestaña
async function loadTabContent(tabID) {
    
    switch (tabID) {
        case 'resumen':
            loadResumenTab();
            break;
        case 'comisiones':
            await loadComisionesTab();
            break;
        case 'mi-red':
            await loadRedTab();
            break;
        case 'ventas':
            await loadVentasTab();
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
        case 'enlace':
            loadEnlaceTab();
            break;
        case 'tienda':
            await loadTiendaTab();
            break;
    }
}

// Cargar pestaña inicial
function loadInitialTab() {
    const activeTab = document.querySelector(".sidebar nav li.active");
    if (activeTab) {
        loadTabContent(activeTab.dataset.tab);
    } else {
        // Activar pestaña resumen por defecto
        const resumenTab = document.querySelector('[data-tab="resumen"]');
        if (resumenTab) {
            resumenTab.click();
        }
    }
}

// ===== FUNCIONES DE PESTAÑAS =====

// Pestaña Resumen
function loadResumenTab() {
    if (!dashboardData) return;
    
    const resumenContainer = document.getElementById('resumen-container');
    if (!resumenContainer) return;
    
    const stats = dashboardData.estadisticas;
    const datosRecientes = dashboardData.datos_recientes;
    
    resumenContainer.innerHTML = `
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Comisiones Totales</h3>
                <p class="stat-value">${formatCurrency(stats.comisiones.total_ganado || 0)}</p>
                <p class="stat-label">${stats.comisiones.total_comisiones || 0} transacciones</p>
            </div>
            <div class="stat-card">
                <h3>Comisiones Pendientes</h3>
                <p class="stat-value pending">${formatCurrency(stats.comisiones.comisiones_pendientes || 0)}</p>
                <p class="stat-label">Por procesar</p>
            </div>
            <div class="stat-card">
                <h3>Mi Red</h3>
                <p class="stat-value">${stats.total_afiliados_red || 0}</p>
                <p class="stat-label">Afiliados activos</p>
            </div>
            <div class="stat-card">
                <h3>Ventas Generadas</h3>
                <p class="stat-value">${datosRecientes.ventas.length || 0}</p>
                <p class="stat-label">Este mes</p>
            </div>
        </div>
        
        <div class="recent-activity">
            <h3>Actividad Reciente</h3>
            <div class="activity-list">
                ${generateActivityList(datosRecientes)}
            </div>
        </div>
    `;
}

// Pestaña Comisiones
async function loadComisionesTab() {
    if (!dashboardData) return;
    
    const comisionesContainer = document.getElementById('comisiones-container');
    if (!comisionesContainer) return;
    
    const comisiones = dashboardData.datos_recientes.comisiones;
    const stats = dashboardData.estadisticas;
    
    comisionesContainer.innerHTML = `
        <div class="comisiones-summary">
            <div class="summary-card">
                <h4>Total Ganado</h4>
                <p class="amount">${formatCurrency(stats.comisiones.total_ganado || 0)}</p>
            </div>
            <div class="summary-card">
                <h4>Pendientes</h4>
                <p class="amount pending">${formatCurrency(stats.comisiones.comisiones_pendientes || 0)}</p>
            </div>
            <div class="summary-card">
                <h4>Pagadas</h4>
                <p class="amount paid">${formatCurrency(stats.comisiones.comisiones_pagadas || 0)}</p>
            </div>
        </div>
        
        <div class="comisiones-table">
            <h3>Comisiones Recientes</h3>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Libro</th>
                        <th>Comprador</th>
                        <th>Nivel</th>
                        <th>Monto</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    ${comisiones.map(c => `
                        <tr>
                            <td>${formatDate(c.fecha_generacion)}</td>
                            <td>${c.libro_titulo}</td>
                            <td>${c.comprador_nombre}</td>
                            <td>Nivel ${c.nivel}</td>
                            <td>${formatCurrency(c.monto)}</td>
                            <td><span class="status ${c.estado}">${c.estado}</span></td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

// Pestaña Mi Red
async function loadRedTab() {
    if (!dashboardData) return;
    
    const redContainer = document.getElementById('mi-red-container');
    if (!redContainer) return;
    
    const redAfiliados = dashboardData.datos_recientes.red_afiliados;
    const statsRed = dashboardData.estadisticas.red;
    
    redContainer.innerHTML = `
        <div class="red-summary">
            <h3>Resumen de Mi Red</h3>
            <div class="red-stats">
                ${Object.values(statsRed).map(nivel => `
                    <div class="nivel-stat">
                        <h4>Nivel ${nivel.nivel}</h4>
                        <p>${nivel.total_afiliados} afiliados</p>
                        <p>${formatCurrency(nivel.comision_total)} en comisiones</p>
                    </div>
                `).join('')}
            </div>
        </div>
        
        <div class="red-afiliados">
            <h3>Mis Afiliados Directos</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Código</th>
                        <th>Nivel</th>
                        <th>Estado</th>
                        <th>Comisión Total</th>
                        <th>Ventas</th>
                    </tr>
                </thead>
                <tbody>
                    ${redAfiliados.map(af => `
                        <tr>
                            <td>${af.nombre}</td>
                            <td>${af.codigo_afiliado}</td>
                            <td>Nivel ${af.nivel}</td>
                            <td><span class="status ${af.estado_usuario}">${af.estado_usuario}</span></td>
                            <td>${formatCurrency(af.comision_total)}</td>
                            <td>${af.ventas_totales}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

// Pestaña Ventas
async function loadVentasTab() {
    if (!dashboardData) return;
    
    const ventasContainer = document.getElementById('ventas-container');
    if (!ventasContainer) return;
    
    const ventas = dashboardData.datos_recientes.ventas;
    
    ventasContainer.innerHTML = `
        <div class="ventas-summary">
            <h3>Ventas Generadas</h3>
            <p>Total de ventas: ${ventas.length}</p>
        </div>
        
        <div class="ventas-table">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Libro</th>
                        <th>Comprador</th>
                        <th>Precio</th>
                        <th>Comisión</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    ${ventas.map(v => `
                        <tr>
                            <td>${formatDate(v.fecha_venta)}</td>
                            <td>${v.libro_titulo}</td>
                            <td>${v.comprador_nombre}</td>
                            <td>${formatCurrency(v.precio_venta)}</td>
                            <td>${formatCurrency(v.comision_generada || 0)}</td>
                            <td><span class="status ${v.estado}">${v.estado}</span></td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

// Pestaña Retiros
async function loadRetirosTab() {
    if (!dashboardData) return;
    
    const retirosContainer = document.getElementById('retiros-container');
    if (!retirosContainer) return;
    
    const retiros = dashboardData.datos_recientes.retiros;
    
    retirosContainer.innerHTML = `
        <div class="retiros-header">
            <h3>Historial de Retiros</h3>
            <button class="btn-primary" onclick="solicitarRetiro()">Solicitar Retiro</button>
        </div>
        
        <div class="retiros-table">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Estado</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    ${retiros.map(r => `
                        <tr>
                            <td>${formatDate(r.fecha_solicitud)}</td>
                            <td>${formatCurrency(r.monto)}</td>
                            <td>${r.metodo_retiro}</td>
                            <td><span class="status ${r.estado}">${r.estado}</span></td>
                            <td>${r.notas_admin || '-'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

// Pestaña Campañas
async function loadCampanasTab() {
    if (!dashboardData) return;
    
    const campanasContainer = document.getElementById('campanas-container');
    if (!campanasContainer) return;
    
    const campanas = dashboardData.campanas_activas;
    
    campanasContainer.innerHTML = `
        <div class="campanas-header">
            <h3>Campañas Activas</h3>
        </div>
        
        <div class="campanas-grid">
            ${campanas.map(c => `
                <div class="campana-card">
                    <h4>${c.nombre}</h4>
                    <p>${c.descripcion}</p>
                    <div class="campana-details">
                        <p><strong>Comisión Extra:</strong> ${c.comision_extra}%</p>
                        <p><strong>Válida hasta:</strong> ${formatDate(c.fecha_fin)}</p>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}

// Pestaña Material Promocional
async function loadMaterialTab() {
    const materialContainer = document.getElementById('material-container');
    if (!materialContainer) return;
    
    materialContainer.innerHTML = `
        <div class="material-header">
            <h3>Material Promocional</h3>
        </div>
        
        <div class="material-grid">
            <div class="material-card">
                <h4>Banners</h4>
                <div class="banner-list">
                    <div class="banner-item">
                        <img src="images/banner_horizontal.jpg" alt="Banner Horizontal">
                        <button onclick="copiarEnlace('banner_horizontal.jpg')">Copiar Enlace</button>
                    </div>
                    <div class="banner-item">
                        <img src="images/banner_vertical.jpg" alt="Banner Vertical">
                        <button onclick="copiarEnlace('banner_vertical.jpg')">Copiar Enlace</button>
                    </div>
                </div>
            </div>
            
            <div class="material-card">
                <h4>Enlaces de Afiliado</h4>
                <div class="enlace-item">
                    <p><strong>Tu enlace principal:</strong></p>
                    <div class="enlace-display">
                        <input type="text" id="enlace-principal" value="${userData?.enlace_afiliado || ''}" readonly>
                        <button onclick="copiarEnlace('enlace-principal')">Copiar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Pestaña Enlace
function loadEnlaceTab() {
    const enlaceContainer = document.getElementById('enlace-container');
    if (!enlaceContainer) return;
    
    enlaceContainer.innerHTML = `
        <div class="enlace-header">
            <h3>Tu Enlace de Afiliado</h3>
        </div>
        
        <div class="enlace-content">
            <div class="qr-section">
                <h4>Código QR</h4>
                <div class="qr-code">
                    <img src="images/qr_101.png" alt="QR Code" id="qr-code">
                </div>
                <button onclick="descargarQR()">Descargar QR</button>
            </div>
            
            <div class="enlace-section">
                <h4>Enlace de Referido</h4>
                <div class="enlace-input">
                    <input type="text" id="enlace-afiliado" value="${userData?.enlace_afiliado || ''}" readonly>
                    <button onclick="copiarEnlace('enlace-afiliado')">Copiar</button>
                </div>
                <p class="enlace-info">Comparte este enlace para ganar comisiones por cada registro</p>
            </div>
        </div>
    `;
}

// Pestaña Tienda
async function loadTiendaTab() {
    const tiendaContainer = document.getElementById('tienda-container');
    if (!tiendaContainer) return;
    
    try {
        const response = await fetch('api/libros/disponibles.php');
        const data = await response.json();
        
        if (data.success) {
            tiendaContainer.innerHTML = `
                <div class="tienda-header">
                    <h3>Libros Disponibles</h3>
                </div>
                
                <div class="libros-grid">
                    ${data.libros.map(libro => `
                        <div class="libro-card">
                            <img src="${libro.imagen_portada || 'images/libros/default.jpg'}" alt="${libro.titulo}">
                            <h4>${libro.titulo}</h4>
                            <p>${libro.descripcion}</p>
                            <div class="libro-precio">
                                <span class="precio-original">$${libro.precio}</span>
                                <span class="precio-afiliado">$${libro.precio_afiliado}</span>
                            </div>
                            <button onclick="comprarLibro(${libro.id})">Comprar</button>
                        </div>
                    `).join('')}
                </div>
            `;
        } else {
            tiendaContainer.innerHTML = '<p>Error cargando libros</p>';
        }
    } catch (error) {
        console.error('Error cargando tienda:', error);
        tiendaContainer.innerHTML = '<p>Error cargando libros</p>';
    }
}

// ===== FUNCIONES UTILITARIAS =====

// Generar lista de actividad
function generateActivityList(datosRecientes) {
    const activities = [];
    
    // Agregar comisiones recientes
    datosRecientes.comisiones.slice(0, 3).forEach(c => {
        activities.push({
            type: 'comision',
            text: `Comisión de $${c.monto} por "${c.libro_titulo}"`,
            date: c.fecha_generacion
        });
    });
    
    // Agregar ventas recientes
    datosRecientes.ventas.slice(0, 3).forEach(v => {
        activities.push({
            type: 'venta',
            text: `Venta de "${v.libro_titulo}" por $${v.precio_venta}`,
            date: v.fecha_venta
        });
    });
    
    // Ordenar por fecha
    activities.sort((a, b) => new Date(b.date) - new Date(a.date));
    
    return activities.slice(0, 5).map(activity => `
        <div class="activity-item">
            <span class="activity-icon ${activity.type}"></span>
            <div class="activity-content">
                <p>${activity.text}</p>
                <small>${formatDate(activity.date)}</small>
            </div>
        </div>
    `).join('');
}

// Formatear moneda
function formatCurrency(amount) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(amount);
}

// Formatear fecha
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Actualizar elemento de estadística
function updateStatElement(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
    }
}

// Actualizar elemento
function updateElement(id, value) {
    const el = document.getElementById(id);
    if (el) {
        if (el.tagName === 'INPUT') {
            el.value = value;
        } else {
            el.textContent = value;
        }
    }
}

// Mostrar loading
function showLoading(message = 'Cargando...') {
    const loading = document.getElementById('loading-overlay');
    if (loading) {
        loading.style.display = 'flex';
        const messageEl = loading.querySelector('.loading-message');
        if (messageEl) messageEl.textContent = message;
    }
}

// Ocultar loading
function hideLoading() {
    const loading = document.getElementById('loading-overlay');
    if (loading) {
        loading.style.display = 'none';
    }
}

// Mostrar error
function showError(message) {
    const notification = document.createElement('div');
    notification.className = 'notification error';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Mostrar éxito
function showSuccess(message) {
    const notification = document.createElement('div');
    notification.className = 'notification success';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Copiar enlace
function copiarEnlace(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.select();
        document.execCommand('copy');
        showSuccess('Enlace copiado al portapapeles');
    }
}

// Cerrar sesión
function logout() {
    localStorage.removeItem('session_token');
    localStorage.removeItem('user_data');
    window.location.href = 'index.html';
}

// Solicitar retiro
function solicitarRetiro() {
    // Implementar modal de solicitud de retiro
    alert('Función de solicitud de retiro en desarrollo');
}

// Comprar libro
function comprarLibro(libroId) {
    window.location.href = `pago.html?libro=${libroId}`;
}

// Descargar QR
function descargarQR() {
    const qrImage = document.getElementById('qr-code');
    if (qrImage) {
        const link = document.createElement('a');
        link.download = 'qr-afiliado.png';
        link.href = qrImage.src;
        link.click();
    }
}

// Asociar logout al botón si no está
const logoutBtn = document.querySelector('.logout');
if (logoutBtn) {
    logoutBtn.addEventListener('click', function(e) {
        e.preventDefault();
        logout();
    });
} 