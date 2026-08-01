/* Carga Leaflet solo cuando un mapa se acerca al viewport. */
(() => {
  const LEAFLET_CSS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
  const LEAFLET_JS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
  let promise;

  window.cargarLeaflet = function cargarLeaflet() {
    if (window.L) return Promise.resolve(window.L);
    if (promise) return promise;
    promise = new Promise((resolve, reject) => {
      if (!document.querySelector('link[data-leaflet]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = LEAFLET_CSS;
        link.dataset.leaflet = '1';
        document.head.appendChild(link);
      }
      const script = document.createElement('script');
      script.src = LEAFLET_JS;
      script.async = true;
      script.dataset.leaflet = '1';
      script.onload = () => resolve(window.L);
      script.onerror = () => reject(new Error('No se pudo cargar Leaflet'));
      document.head.appendChild(script);
    });
    return promise;
  };

  window.alEntrarEnVista = function alEntrarEnVista(elemento, callback, margen = '250px') {
    if (!elemento) return;
    if (!('IntersectionObserver' in window)) { callback(); return; }
    const observer = new IntersectionObserver((entries) => {
      if (entries.some(e => e.isIntersecting)) {
        observer.disconnect();
        callback();
      }
    }, { rootMargin: margen });
    observer.observe(elemento);
  };

  // ── Cluster de marcadores (punto 9 del pedido: agrupar pines cercanos) ──
  const CLUSTER_CSS = 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css';
  const CLUSTER_CSS_DEFAULT = 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css';
  const CLUSTER_JS = 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js';
  let clusterPromise;

  window.cargarMarkerCluster = function cargarMarkerCluster() {
    if (window.L && window.L.markerClusterGroup) return Promise.resolve(window.L);
    if (clusterPromise) return clusterPromise;
    clusterPromise = cargarLeaflet().then(() => new Promise((resolve, reject) => {
      [CLUSTER_CSS, CLUSTER_CSS_DEFAULT].forEach(href => {
        if (!document.querySelector(`link[href="${href}"]`)) {
          const link = document.createElement('link');
          link.rel = 'stylesheet'; link.href = href;
          document.head.appendChild(link);
        }
      });
      const script = document.createElement('script');
      script.src = CLUSTER_JS;
      script.async = true;
      script.onload = () => resolve(window.L);
      script.onerror = () => reject(new Error('No se pudo cargar el cluster de marcadores'));
      document.head.appendChild(script);
    }));
    return clusterPromise;
  };
})();
