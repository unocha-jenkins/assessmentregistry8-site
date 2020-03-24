// Import the LitElement base class and html helper function
import { LitElement, html } from 'lit-element';
import { OchaAssessmentsBase } from './ocha-assessments-base.js';
import { Map } from '../node_modules/leaflet/src/map';
import { TileLayer } from '../node_modules/leaflet/src/layer/tile';

// Need these side effects
import '../node_modules/leaflet/src/control';
import '../node_modules/leaflet/src/layer';

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

  connectedCallback() {
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

