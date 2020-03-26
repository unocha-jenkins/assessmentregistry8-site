import { html, css } from 'lit-element';
import { OchaAssessmentsBase } from './ocha-assessments-base.js';
import { Map } from 'leaflet/src/map';
import { TileLayer } from 'leaflet/src/layer/tile';
import { Marker } from 'leaflet/src/layer/marker';
import { FeatureGroup } from 'leaflet/src/layer';
import { MarkerClusterGroup } from 'leaflet.markercluster/src';

// Extend the LitElement base class
class OchaAssessmentsMap extends OchaAssessmentsBase {
  static get styles() {
    return [
      super.styles,
      css`
        :host { display: block;
          border: 1px solid green;
        }`
    ]
  }

  render() {
    // Build facets.
    let dropdowns = this.buildFacets();

    return html`
    <link rel="stylesheet" href="./leaflet.css" />
    <link rel="stylesheet" href="./MarkerCluster.Default.css" />

      <style>
        #map {
          width: 100%;
          height: 100%;
          @apply (--leaflet-map-component)
        }
      </style>

      <p>Source (debug): ${this.src}</p>
      <div class="filters">
        ${
          dropdowns.map(
            d => this.renderDropdown(d)
          )
        }

        <button @click="${this.resetData}">Reset</button>
      </div>

      <div id="map">
        <slot></slot>
      </div>
    `;
  }

  addMarkers() {
    let markers = [];

    if (!this.cluster) {
      this.cluster = new MarkerClusterGroup();
    }
    else  {
      this.cluster.clearLayers();
    }

    this.data.forEach(row => {
      if (typeof row.field_locations_lat_lon != 'undefined' && row.field_locations_lat_lon) {
        const latlon = row.field_locations_lat_lon[0].split(',');
        // Skip empty markers.
        if (latlon[1] != '' && latlon[0] != '') {
          const m = new Marker([latlon[1], latlon[0]]);
          m.bindPopup('<a href="' + this.baseurl + '/node/' + row.nid + '">' + row.title + '</a>');
          markers.push(m);
          this.cluster.addLayer(m);
        }
      }
    });

    this.map.addLayer(this.cluster);
    this.map.fitBounds(this.cluster.getBounds());
  }

  connectedCallback() {
    this.fetchCb = this.addMarkers;
    super.connectedCallback();
  }

  firstUpdated(changedProperties) {
    if (!this.map) {
      this.map = new Map(this.shadowRoot.getElementById('map'), {
        center: [this.latitude, this.longitude],
        zoom: this.zoom,
        zoomControl: this.zoomControl,
        inertiaDeceleration: 3000,
        inertiaMaxSpeed: 3000,
        attributionControl: false,
        minZoom: this.minZoom,
        maxZoom: this.maxZoom,
        tapTolerance: 40,
        tap: false
      });

      this.map.setView([this.latitude, this.longitude], this.zoom);

      const l = new TileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 17,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      });
      l.addTo(this.map);
    }
  }

  static get properties() {
    return {
      baseurl: {
        type: String
      },
      src: {
        type: String
      },
      data: {
        type: Array
      },
      map: {
        type: Object
      },
      latitude: {
        type: Number
      },
      longitude: {
        type: Number
      },
      zoom: {
        type: Number
      },
      minZoom: {
        type: Number
      },
      maxZoom: {
        type: Number
      },
      zoomControl: {
        type: Boolean,
        value: false
      },
    };
  }

}

customElements.define('ocha-assessments-map', OchaAssessmentsMap);

