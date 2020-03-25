// Import the LitElement base class and html helper function
import { LitElement, html } from 'lit-element';
import { OchaAssessmentsBase } from './ocha-assessments-base.js';
import { Map } from '../node_modules/leaflet/src/map';
import { TileLayer } from '../node_modules/leaflet/src/layer/tile';
import { Marker } from '../node_modules/leaflet/src/layer/marker';
import { featureGroup } from '../node_modules/leaflet/src/layer';

// Extend the LitElement base class
class OchaAssessmentsMap extends OchaAssessmentsBase {

  render() {
    // Build facets.
    let dropdowns = this.buildFacets();

    return html`
      <link rel="stylesheet" href="./leaflet.css" />

      <style>
        #map {
          width: 100%;
          height: 100%;
          @apply (--leaflet-map-component)
        }
      </style>

      <p>Source (debug): ${this.src}</p>

      <div id="map">
        <slot></slot>
      </div>
    `;
  }

  addMarkers() {
    let markers = [];

    this.data.forEach(row => {
      if (typeof row.field_locations_lat_lon != 'undefined' && row.field_locations_lat_lon) {
        const latlon = row.field_locations_lat_lon[0].split(',');
        // Skip empty markers.
        if (latlon[1] != '' && latlon[0] != '') {
          const m = new Marker([latlon[1], latlon[0]]);
          markers.push(m);
        }
      }
    });

    var fg = new featureGroup(markers).addTo(this.map);
    this.map.fitBounds(fg.getBounds());
  }

  connectedCallback() {
    this.fetchCb = this.addMarkers;
    super.connectedCallback();
  }

  firstUpdated(changedProperties) {
    console.log(this.shadowRoot.getElementById('map'));
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
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      });
      l.addTo(this.map);
    }
  }

  static get properties() {
    return {
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

