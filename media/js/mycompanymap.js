document.addEventListener('DOMContentLoaded', async function () {
    const roots = document.querySelectorAll('.my-company-map-module[id^="mod-mycompanymap-"]');
    if (typeof ymaps3 === 'undefined' || !ymaps3.ready) {
        roots.forEach(root => {
            const placeholder = root.querySelector('.map-loading-placeholder');
            if (placeholder) { const id = root.id.replace('mod-mycompanymap-', ''); placeholder.textContent = window['modMyCompanyMapOptions' + id]?.messages?.loadError || 'Could not load the map.'; }
        });
        return;
    }
    for (const root of roots) {
    const moduleId = root.id.replace('mod-mycompanymap-', '');
    const options = window['modMyCompanyMapOptions' + moduleId];
    if (!options) continue;
    try {
    await ymaps3.ready;

    const { YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer, YMapFeatureDataSource, YMapLayer, YMapMarker } = ymaps3;
    const mapContainer = document.getElementById(options.mapId);
    if (!mapContainer) continue;

    const packages = ['@yandex/ymaps3-clusterer@0.0.13'];
    if (!options.customMarker) packages.push('@yandex/ymaps3-default-ui-theme@0.0.25');
    ymaps3.import.registerCdn('https://cdn.jsdelivr.net/npm/{package}', packages);
    const [{ YMapDefaultMarker }, { YMapClusterer, clusterByGrid }] = await Promise.all([
        options.customMarker ? Promise.resolve({}) : ymaps3.import('@yandex/ymaps3-default-ui-theme'),
        ymaps3.import('@yandex/ymaps3-clusterer')
    ]);

    const allCoords = [];
    let currentBalloon = null;
    const allFeatures = [];

    options.companies.forEach(company => {
        if (Array.isArray(company.coords) && company.coords.length === 2 &&
            typeof company.coords[0] === 'number' && typeof company.coords[1] === 'number')
        {
            allCoords.push(company.coords);
            allFeatures.push({
                type: 'Feature',
                id: company.id,
                geometry: { type: 'Point', coordinates: company.coords },
                properties: { ...company }
            });
        }
    });

    let defaultCenter = options.defaultCenter || [37.618423, 55.751244];
    let defaultZoom = options.defaultZoom || 10;

    let mapOptions = {
        location: {
            center: defaultCenter,
            zoom: defaultZoom
        },
        margin: [60, 60, 60, 60]
    };
    if (mapContainer.clientWidth > 0 && mapContainer.clientHeight > 0) {
        const marginPx = Math.max(64, Math.min(120, Math.min(mapContainer.clientWidth, mapContainer.clientHeight) * 0.23));
        mapOptions.margin = [marginPx, marginPx, marginPx, marginPx];
    }

    // Optional floating content can reserve visible space when fitting bounds.
    const overlay = options.overlaySelector ? root.querySelector(options.overlaySelector) : null;
    if (overlay) {
        const mapRect = mapContainer.getBoundingClientRect();
        const overlayRect = overlay.getBoundingClientRect();
        const bottomInset = Math.max(0, mapRect.bottom - overlayRect.top);
        mapOptions.margin = [64, 64, Math.max(0, Math.min(mapRect.height - 100, bottomInset + 40)), 64];
    }

    const loading = mapContainer.querySelector('.map-loading-placeholder');
    if (loading) loading.hidden = true;
    const map = new YMap(mapContainer, mapOptions);
    map.addChild(new YMapDefaultSchemeLayer({}));
    if (options.showDefaultFeatures !== false) {
        map.addChild(new YMapDefaultFeaturesLayer({}));
    }
    // Keep company pins on their own source so they remain visible when Yandex POIs are hidden.
    const markerSource = 'mycompanymap-source-' + moduleId;
    map.addChild(new YMapFeatureDataSource({ id: markerSource }));
    map.addChild(new YMapLayer({ source: markerSource, type: 'markers', zIndex: 1800 }));

    function showBalloon(companyData) {
        if (currentBalloon) {
            try { map.removeChild(currentBalloon); } catch (e) { }
            currentBalloon = null;
        }
        if (!companyData || !Array.isArray(companyData.coords) || companyData.coords.length !== 2) return;

        map.update({ location: { center: companyData.coords, zoom: 15, duration: 300 } });

        const balloonTemplate = root.querySelector('template[data-map-balloon]');
        const balloonContent = balloonTemplate?.content.firstElementChild;
        const balloonDiv = balloonContent?.cloneNode(true) || document.createElement('div');
        balloonDiv.classList.add('mycompanymap-balloon');
        if (!balloonContent) {
            balloonDiv.innerHTML = '<strong data-balloon-title></strong><div data-balloon-address></div><a data-balloon-phone></a><button type="button" data-balloon-close>×</button>';
        }
        const setText = (selector, value) => {
            const el = balloonDiv.querySelector(selector);
            if (!el) return;
            el.textContent = value || '';
            el.hidden = !value;
        };
        setText('[data-balloon-title]', companyData.name);
        setText('[data-balloon-address]', companyData.company_address);
        setText('[data-balloon-phone]', companyData.company_phone);
        const phone = balloonDiv.querySelector('[data-balloon-phone]');
        if (phone && phone.tagName === 'A' && companyData.company_phone) {
            phone.href = 'tel:' + companyData.company_phone.replace(/[^0-9+]/g, '');
        }
        const close = balloonDiv.querySelector('[data-balloon-close]');
        if (close) {
            close.title = options.messages?.close || 'Close';
            close.setAttribute('aria-label', options.messages?.close || 'Close');
            close.onclick = function (e) {
                e.stopPropagation();
                if (currentBalloon) {
                    try { map.removeChild(currentBalloon); } catch (error) {}
                    currentBalloon = null;
                }
            };
        }

        const balloonMarker = new YMapMarker({ coordinates: companyData.coords, source: markerSource, zIndex: 2500 }, balloonDiv);
        map.addChild(balloonMarker);
        currentBalloon = balloonMarker;
    }

    // --- Функция для создания маркера с цветом из options ---
    const createMarkerElement = (feature) => {
        if (options.customMarker) {
            const pin = document.createElement('button');
            pin.type = 'button';
            pin.className = 'mycompanymap-custom-marker';
            pin.setAttribute('aria-label', feature.properties.name || 'Метка на карте');
            const icon = document.createElement('span');
            icon.className = 'mycompanymap-custom-marker__icon';
            icon.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>';
            pin.append(icon);
            if (options.markerLabels && feature.properties.name) {
                const label = document.createElement('span');
                label.className = 'mycompanymap-custom-marker__label';
                label.textContent = feature.properties.name;
                pin.append(label);
            }
            pin.addEventListener('click', () => showBalloon(feature.properties));
            return new YMapMarker({ coordinates: feature.geometry.coordinates, source: markerSource }, pin);
        }
        return new YMapDefaultMarker({
            coordinates: feature.geometry.coordinates,
            source: markerSource,
            title: feature.properties.name || '',
            draggable: false,
            color: options.icon_color || '#4DA6FF',
            onClick: () => showBalloon(feature.properties)
        });
    };

    // --- Кластер с зумом при клике ---
    const createClusterElement = (coordinates, features) => {
        const clusterSize = features.length;
        const clusterDiv = document.createElement('div');
        clusterDiv.style.cssText = `
            background: rgba(0, 119, 255, 0.8); color: white;
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            font-size: 14px; font-weight: bold; cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        `;
        clusterDiv.textContent = clusterSize;

        const clusterMarker = new YMapMarker({ coordinates: coordinates, source: markerSource, zIndex: 1500 }, clusterDiv);

        // --- Зум при клике на кластер ---
        clusterDiv.addEventListener('click', function (e) {
            e.stopPropagation();
            // Получаем координаты всех меток в кластере
            const clusterCoords = features.map(f => f.geometry.coordinates);
            // Если одна метка — просто центрируем и увеличиваем зум
            if (clusterCoords.length === 1) {
                map.update({ location: { center: clusterCoords[0], zoom: 16, duration: 300 } });
            } else {
                // Вычисляем границы и увеличиваем зум
                const bounds = getBounds(clusterCoords);
                if (bounds) {
                    map.update({ location: { bounds: bounds, duration: 400, easing: 'ease-in-out' } });
                }
            }
            if (currentBalloon) {
                try { map.removeChild(currentBalloon); } catch (e) {}
                currentBalloon = null;
            }
        });

        return clusterMarker;
    };

    const clusterer = new YMapClusterer({
        features: allFeatures,
        method: clusterByGrid({ gridSize: 100 }),
        marker: createMarkerElement,
        cluster: createClusterElement
    });

    map.addChild(clusterer);

    root.addEventListener('click', function (e) {
        const button = e.target.closest('.center-on-map-btn');
        const card = button && button.closest('.company-card');
        if (!card || !root.contains(card)) return;
        const companyId = card.getAttribute('data-placemarkid');
        const company = options.companies.find(c => String(c.id) === String(companyId));
        const feature = allFeatures.find(f => String(f.id) === String(companyId));
        if (!feature || !feature.properties) return;
        showBalloon(feature.properties);
    });

    function showHashTarget() {
        if (!location.hash) return;
        const target = document.getElementById(location.hash.slice(1));
        if (!target || !root.contains(target) || !target.classList.contains('company-card')) return;
        const company = options.companies.find(c => String(c.id) === target.getAttribute('data-placemarkid'));
        if (company) showBalloon(company);
    }
    window.addEventListener('hashchange', showHashTarget);
    showHashTarget();

    function getBounds(coordinates) {
        if (!coordinates || coordinates.length === 0) return null;
        let minLat = Infinity, minLng = Infinity;
        let maxLat = -Infinity, maxLng = -Infinity;
        for (const coords of coordinates) {
            if (!Array.isArray(coords) || coords.length !== 2 || typeof coords[0] !== 'number' || typeof coords[1] !== 'number' || !isFinite(coords[0]) || !isFinite(coords[1])) {
                continue;
            }
            const lng = coords[0];
            const lat = coords[1];
            if (lat < minLat) minLat = lat;
            if (lat > maxLat) maxLat = lat;
            if (lng < minLng) minLng = lng;
            if (lng > maxLng) maxLng = lng;
        }
        if (!isFinite(minLng) || !isFinite(minLat) || !isFinite(maxLng) || !isFinite(maxLat)) {
            return null;
        }
        const epsilon = 1e-6;
        if (Math.abs(maxLng - minLng) < epsilon && Math.abs(maxLat - minLat) < epsilon) {
            maxLng += epsilon;
            maxLat += epsilon;
            minLng -= epsilon;
            minLat -= epsilon;
        }
        return [ [minLng, minLat], [maxLng, maxLat] ];
    }

    function applyBounds(map, coordsArr) {
        if (!coordsArr || coordsArr.length === 0) {
            return;
        }
        if (coordsArr.length === 1) {
            map.update({ location: { center: coordsArr[0], zoom: 15, duration: 300 } });
        } else {
            const bounds = getBounds(coordsArr);
            if (bounds) {
                map.update({ location: { bounds: bounds, duration: 500, easing: 'ease-in-out' } });
            } else {
                map.update({ location: { center: coordsArr[0], zoom: 10, duration: 300 }});
            }
        }
    }

    if (allCoords.length > 0 && !location.hash) {
        setTimeout(() => {
            applyBounds(map, allCoords);
        }, 100);
    }

    function filterMapFeatures(city) {
        if (currentBalloon) {
            try { map.removeChild(currentBalloon); } catch (e) { }
            currentBalloon = null;
        }
        const filteredFeatures = (!city || city === 'all')
            ? allFeatures
            : allFeatures.filter(feature => feature.properties.company_city === city);

        clusterer.update({ features: filteredFeatures });
        return filteredFeatures.map(f => f.geometry.coordinates);
    }

    var filter = document.getElementById(options.filterId);
    if (filter) {
        filter.addEventListener('change', function () {
            var city = filter.value;
            document.querySelectorAll('#' + options.cardsContainerId + ' .company-card').forEach(function (card) {
                if (!city || city === 'all' || card.getAttribute('data-city') === city) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
            const visibleCoords = filterMapFeatures(city);
            applyBounds(map, visibleCoords);
        });
    }

    if (loading) loading.remove();
    if (mapContainer && mapContainer.parentElement.classList) {
        mapContainer.parentElement.classList.add('loaded');
    } else {
        console.warn("Map container parent or classList not found for loaded state.");
    }
    } catch (error) {
        console.error('Не удалось загрузить карту:', error);
        const placeholder = root.querySelector('.map-loading-placeholder');
        if (placeholder) { placeholder.hidden = false; placeholder.textContent = options.messages?.loadError || 'Could not load the map.'; }
    }
    }
});
