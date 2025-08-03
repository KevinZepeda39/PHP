// Funcionalidad JavaScript para MiCiudadSv

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Manejar los botones de "Follow"
    const followButtons = document.querySelectorAll('.follow-btn');
    followButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (this.textContent === '+ Follow') {
                this.textContent = 'Following';
                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-primary');
            } else {
                this.textContent = '+ Follow';
                this.classList.remove('btn-primary');
                this.classList.add('btn-outline-primary');
            }
        });
    });
    
    // Manejar botones de "Me gusta"
    const likeButtons = document.querySelectorAll('.btn-outline-primary i.far.fa-thumbs-up');
    likeButtons.forEach(button => {
        button.parentElement.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if (icon.classList.contains('far')) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                // Aquí se podría incrementar el contador
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                // Aquí se podría decrementar el contador
            }
        });
    });
    
    // Auto-expandir textarea al escribir
    const autoExpandTextareas = document.querySelectorAll('textarea');
    autoExpandTextareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
    
    // Manejar el cierre automático de alertas
    const autoCloseAlerts = document.querySelectorAll('.alert:not(.alert-danger)');
    autoCloseAlerts.forEach(alert => {
        setTimeout(() => {
            const closeButton = alert.querySelector('.btn-close');
            if (closeButton) {
                closeButton.click();
            }
        }, 5000); // Cerrar después de 5 segundos
    });
    
    // Validación básica de formularios
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Añadir comportamiento para las pestañas de navegación
    const navTabs = document.querySelectorAll('.nav-tabs .nav-link');
    navTabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            // Si hubiera contenido asociado a las pestañas, aquí se manejaría
            navTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
});



// main.js - Funcionalidad para la página de comunidades

document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const searchInput = document.getElementById('searchCommunity');
    const filterButtons = document.querySelectorAll('.filter-btn');
    const categoryPills = document.querySelectorAll('.category-pill');
    const communityCards = document.querySelectorAll('.community-card');
    const infoButtons = document.querySelectorAll('.info-btn');
    
    // Estado de los filtros
    let currentFilter = 'all';
    let currentCategory = 'all';
    let searchTerm = '';

    // Función principal de filtrado
    function applyAllFilters() {
        let visibleCount = 0;
        
        communityCards.forEach(card => {
            let shouldShow = true;

            // Filtro por búsqueda
            if (searchTerm) {
                const title = card.querySelector('h3')?.textContent.toLowerCase() || '';
                const description = card.querySelector('p')?.textContent.toLowerCase() || '';
                const tags = Array.from(card.querySelectorAll('.tag')).map(tag => tag.textContent.toLowerCase());
                
                const matchesSearch = title.includes(searchTerm) || 
                                    description.includes(searchTerm) ||
                                    tags.some(tag => tag.includes(searchTerm));
                
                if (!matchesSearch) shouldShow = false;
            }

            // Filtro por categoría
            if (currentCategory !== 'all' && shouldShow) {
                const cardCategory = card.getAttribute('data-category');
                if (cardCategory !== currentCategory) shouldShow = false;
            }

            // Filtro por tipo (popular, reciente, mis comunidades)
            if (currentFilter !== 'all' && shouldShow) {
                switch(currentFilter) {
                    case 'popular':
                        const membersCount = parseInt(card.getAttribute('data-members') || '0');
                        if (membersCount < 5) shouldShow = false;
                        break;
                    case 'recent':
                        // Aquí podrías filtrar por fecha si tienes ese dato
                        break;
                    case 'my':
                        // Por ahora ocultaremos todas (implementar lógica de usuario después)
                        shouldShow = false;
                        break;
                }
            }

            // Aplicar visibilidad
            card.style.display = shouldShow ? 'block' : 'none';
            if (shouldShow) visibleCount++;
        });

        // Mostrar mensaje si no hay resultados
        checkNoResults(visibleCount);
    }

    // Verificar si no hay resultados
    function checkNoResults(count) {
        let noResultsDiv = document.querySelector('.no-results');
        const grid = document.querySelector('.communities-grid');
        
        if (count === 0) {
            if (!noResultsDiv) {
                noResultsDiv = document.createElement('div');
                noResultsDiv.className = 'no-results text-center py-5';
                noResultsDiv.innerHTML = `
                    <h3>No se encontraron comunidades</h3>
                    <p>Intenta con otros filtros o términos de búsqueda</p>
                `;
                grid.parentNode.insertBefore(noResultsDiv, grid.nextSibling);
            }
            noResultsDiv.style.display = 'block';
        } else {
            if (noResultsDiv) {
                noResultsDiv.style.display = 'none';
            }
        }
    }

    // Event Listeners

    // Búsqueda con debounce
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchTerm = e.target.value.toLowerCase();
                applyAllFilters();
            }, 300);
        });
    }

    // Filtros de tipo (Todas, Populares, Recientes, Mis Comunidades)
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.getAttribute('data-filter');
            applyAllFilters();
        });
    });

    // Filtros de categoría
    categoryPills.forEach(pill => {
        pill.addEventListener('click', function() {
            categoryPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            currentCategory = this.getAttribute('data-category');
            applyAllFilters();
        });
    });

    // Botones de información
    if (infoButtons.length > 0) {
        infoButtons.forEach(button => {
            button.addEventListener('click', function() {
                const card = this.closest('.community-card');
                const title = card.querySelector('h3').textContent;
                const description = card.querySelector('p').textContent;
                const category = card.querySelector('.category-badge').textContent.trim();
                const members = card.querySelector('.members-badge').textContent.trim();
                
                alert(`Información de la comunidad:\n\n` +
                      `Nombre: ${title}\n` +
                      `Categoría: ${category}\n` +
                      `${members}\n\n` +
                      `Descripción: ${description}`);
            });
        });
    }

    // Paginación (si tienes botones de paginación)
    const paginationButtons = document.querySelectorAll('.pagination-btn');
    if (paginationButtons.length > 0) {
        paginationButtons.forEach(button => {
            button.addEventListener('click', function() {
                if (this.classList.contains('page')) {
                    document.querySelectorAll('.pagination-btn.page').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    this.classList.add('active');
                }
            });
        });
    }

    // Inicializar con todos los filtros
    applyAllFilters();
});

// Funciones adicionales para interacción con la interfaz

// Función para manejar el scroll suave
function smoothScroll(target) {
    document.querySelector(target).scrollIntoView({
        behavior: 'smooth'
    });
}

// Función para animaciones de entrada
function animateOnScroll() {
    const elements = document.querySelectorAll('.animate-on-scroll');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
            }
        });
    });

    elements.forEach(element => {
        observer.observe(element);
    });
}

// Ejecutar animaciones cuando la página carga
document.addEventListener('DOMContentLoaded', function() {
    animateOnScroll();
});

// Función para manejar el modal de crear comunidad
function initCreateCommunityModal() {
    const createBtn = document.querySelector('.create-btn');
    const modal = document.getElementById('createCommunityModal');
    const closeModal = document.querySelector('.close-modal');
    const cancelModal = document.querySelector('.cancel-btn');
    const form = document.getElementById('createCommunityForm');

    if (createBtn && modal) {
        createBtn.addEventListener('click', function() {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
    }

    if (closeModal) {
        closeModal.addEventListener('click', function() {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            if (form) form.reset();
        });
    }

    if (cancelModal) {
        cancelModal.addEventListener('click', function() {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            if (form) form.reset();
        });
    }

    // Cerrar modal al hacer clic fuera
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                if (form) form.reset();
            }
        });
    }

    // Cerrar modal con tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.style.display === 'block') {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            if (form) form.reset();
        });
    }


// Función para mostrar más detalles de una comunidad
function showCommunityDetails(communityId) {
    // Aquí podrías hacer una petición AJAX para obtener más detalles
    console.log('Mostrando detalles de la comunidad:', communityId);
}

// Función para unirse/salir de una comunidad
function toggleCommunityMembership(communityId, button) {
    const isJoined = button.classList.contains('joined');
    
    // Aquí harías la petición AJAX para unirse/salir
    if (isJoined) {
        button.classList.remove('joined');
        button.textContent = 'Unirse';
        button.style.backgroundColor = '#3498db';
        button.style.color = 'white';
    } else {
        button.classList.add('joined');
        button.textContent = 'Unido';
        button.style.backgroundColor = '#e3f2fd';
        button.style.color = '#3498db';
    }
}

// Manejo de errores
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger alert-dismissible fade show';
    errorDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(errorDiv, container.firstChild);
    }
}

// Manejo de éxito
function showSuccess(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'alert alert-success alert-dismissible fade show';
    successDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(successDiv, container.firstChild);
    }
}

// Función para cargar más comunidades (si implementas scroll infinito)
function loadMoreCommunities() {
    // Implementar carga de más comunidades
    console.log('Cargando más comunidades...');
}

// Detectar scroll para carga infinita
function initInfiniteScroll() {
    window.addEventListener('scroll', function() {
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 100) {
            loadMoreCommunities();
        }
    });
}

// Función para actualizar contadores en tiempo real
function updateCommunityStats(communityId, type, increment = true) {
    const card = document.querySelector(`[data-community-id="${communityId}"]`);
    if (!card) return;

    const statElement = card.querySelector(`.${type}-count`);
    if (!statElement) return;

    let currentCount = parseInt(statElement.textContent) || 0;
    statElement.textContent = increment ? currentCount + 1 : Math.max(0, currentCount - 1);
}

// Inicializar todo cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar modal de crear comunidad
    initCreateCommunityModal();
    
    // Inicializar scroll infinito (si lo necesitas)
    // initInfiniteScroll();
    
    // Manejar clics en botones de unirse
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('join-btn')) {
            e.preventDefault();
            const communityId = e.target.closest('.community-card').dataset.communityId;
            toggleCommunityMembership(communityId, e.target);
        }
    });

    // Manejar envío de formularios con AJAX
    const createForm = document.getElementById('createCommunityForm');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('crear-comunidad.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('¡Comunidad creada exitosamente!');
                    setTimeout(() => {
                        window.location.href = `comunidad.php?id=${data.communityId}`;
                    }, 1500);
                } else {
                    showError(data.message || 'Error al crear la comunidad');
                }
            })
            .catch(error => {
                showError('Error de conexión. Por favor, intenta de nuevo.');
                console.error('Error:', error);
            });
        });
    }
});

// Exportar funciones para uso global
window.showCommunityDetails = showCommunityDetails;
window.toggleCommunityMembership = toggleCommunityMembership;
window.showError = showError;
window.showSuccess = showSuccess;