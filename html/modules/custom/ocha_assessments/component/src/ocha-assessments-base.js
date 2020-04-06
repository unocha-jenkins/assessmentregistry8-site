import { LitElement, html, css } from 'lit-element';
import './high-select.js';
import { typography, buttonStyles, dropdownStyles } from './ocha-assessments-styles.js';

export class OchaAssessmentsBase extends LitElement {
  constructor() {
    super();
    this.data = void 0;
    this.facets = void 0;
    this.pager = void 0;
    this.hasMultiplePages = false;
    this.resetUrl = null;
  }

  static get styles() {
    return [
      css`
        :host {
          display: block;
          border: 1px solid black;
          --cd-ocha-blue:#026cb6;
          --cd-dark-blue:#025995;
          --cd-bright-blue:#80cbff;
          --cd-highlight-red:#eb5c6d;
          --cd-white:#fff;
          --cd-light-grey:#f2f2f2;
          --cd-mid-grey:#595959;
          --cd-dark-grey:#4a4a4a;
          --cd-black:#000;
          --cd-site-bg-color:#e6ecf1;
          --cd-site-bg-color--light:#ebf0f4;
          --cd-font: helvetica, arial, sans-serif;
          --cd-font-size-base: 16px;
        }`,
      typography,
      buttonStyles,
      dropdownStyles
    ]
  }

  static get properties() {
    return {
      baseurl: {
        type: String
      },
      src: {
        type: String
      },
      componenturl: {
        type: String
      },
      basicAuth: {
        type: String
      },
      errorMessage: {
        type: String
      },
      // @var Array
      disabledFilters: {
        type: Array
      },
      data: {
        type: Array
      }
    };
  }

  setDisabledFilters() {
    this.disabledFilters = [];

    const url = new URL(this.src);
    url.searchParams.forEach((value, name) => {
      if (name.indexOf('f[') === 0) {
        const parts = value.split(':');
        this.disabledFilters.push(parts[0]);
      }
    });
  }

  updated(changedProperties) {
    changedProperties.forEach((oldValue, propName) => {
      if (propName == 'src' && typeof oldValue != 'undefined') {
        this.data = void 0;
        this.fetchData();
      }
    });
  }

  resetData() {
    this.src = this.resetUrl;
  }

  changeSrc(event) {
    this.src = event.currentTarget.value;
  }

  getDropdownLabel(id) {
    const labels = {
      authored_on: 'Date',
      local_groups: 'Local group',
      clusters_sectors: 'Cluster/sector',
      countries: 'Country',
      disasters: 'Disaster',
      locations: 'Location',
      organizations: 'Organization',
      participating_organizations: 'Participating organization',
      population_types: 'Population type',
      themes: 'Theme',
    };

    if (labels[id]) {
      return labels[id];
    }

    return id;
  }

  buildFacets() {
    if (!this.facets) {
      return [];
    }

    let dropdowns = [];

    for (const child_id in this.facets) {
      // Skip disabled filters.
      if (this.disabledFilters.includes(child_id)) {
        continue;
      }

      const child = this.facets[child_id];
      let dropdown = {};

      dropdown = {
        id: child_id,
        label: this.getDropdownLabel(child_id),
        selected: null,
        selected_url: null,
        options: []
      };

      child.forEach(function (option) {
        if (typeof option.values.active != 'undefined') {
          dropdown.selected = option.values.value;
          dropdown.selected_url = option.url;
        }

        dropdown.options.push({
          key: option.url,
          label: option.values.value
        });
      });

      dropdowns.push(dropdown);
    }

    return dropdowns;
  }

  renderDropdowns() {
    // Build facets.
    let dropdowns = this.buildFacets();

    return html`
      <div class="cd-filters">
        <div class="cd-filters__form">
          <form>
            ${
              dropdowns.map(
                d => this.renderDropdown(d)
              )
            }
            <button @click="${this.resetData}">Reset</button>
          </form>
        </div>
      </div>
    `;
  }

  renderDropdown(dropdown) {
    if (dropdown.options.length < 1) {
      return;
    }

    // Sort by label.
    dropdown.options.sort((a, b) => (a.label > b.label ? 1 : b.label > a.label ? -1 : 0));

    // Empty option.
    let emptytOption = {
      label: '- Select -',
      value: ''
    };

    if (dropdown.selected_url) {
      emptytOption.label = '- Remove filter -';
      emptytOption.value = dropdown.selected_url;
    }

    return html`
      <div class="cd-filters__group">
        <label for="${dropdown.label}">${dropdown.label}</label>
        <high-select class="dropdown" search arrow animated @change="${this.changeSrc}" id="${dropdown.id}">
          <high-option value="${emptytOption.value}">${emptytOption.label}</high-option>
          ${
            dropdown.options.map(function (o) {
              if (o.label == dropdown.selected) {
                return html`
                  <high-option value="" selected>${o.label}</high-option>
                `
              }
              else {
                return html`
                  <high-option value="${o.key}">${o.label}</high-option>
                `
              }
            })
          }
        </high-select>
      </div>
    `;
  }

  renderDate(data) {
    let output = data.field_ass_date;

    if (typeof data.field_ass_date_end_value != 'undefined' && data.field_ass_date_end_value && data.field_ass_date_end_value.length > 0) {
      output = output + ' - ' + data.field_ass_date_end_value[0];
    }

    return output;
  }

  prevPage() {
    if (this.pager.current_page > 0) {
      const url = new URL(this.src);
      if (this.pager.current_page - 1 == 0) {
        url.searchParams.delete('page');
      }
      else {
        url.searchParams.set('page', this.pager.current_page - 1);
      }
      this.src = url.toString();
    }
  }

  nextPage() {
    if (this.pager.current_page < this.pager.total_pages) {
      const url = new URL(this.src);
      url.searchParams.set('page', this.pager.current_page + 1);
      this.src = url.toString();
    }
  }

  renderPager() {
    if (!this.pager) {
      return;
    }

    if (this.pager.total_pages <= 1) {
      return;
    }

    return html`
      <div class="pager">
        ${this.pager.current_page > 0?
          html`<button class="pager-prev" @click="${this.prevPage}">Previous</button>`: html``
        }
        <span><span class="page-num">${this.pager.current_page + 1}</span> / <span class="page-total">${this.pager.total_pages}</span></span>
        ${this.pager.current_page < this.pager.total_pages - 1?
          html`<button class="pager-next" @click="${this.nextPage}">Next</button>`: html``
        }
      </div>
    `;
  }

  renderErrorMessage() {
    if (this.errorMessage == '') {
      return;
    }

    return html`
      <div class="error">
        ${this.errorMessage}
      </div>
    `;
  }

  render() {
    if (!this.data) {
      return html`
        <div>Loading...</div>
      `;
    }
  }

  connectedCallback() {
    super.connectedCallback();
    if (this.src) {
      this.fetchData();
      this.resetUrl = this.src;
      this.setDisabledFilters();
    }
    else {
      console.error('src attribute is required.')
    }
  }

  fetchData() {
    var headers = new Headers();
    if (this.basicAuth) {
      headers.append('Authorization', 'Basic ' + btoa(this.basicAuth));
    }

    fetch(this.src, {
      headers: headers
    })
      .then(response => {
        if (response.ok) {
          return response.json();
        }

        this.errorMessage = 'Unable to load data.';
        throw new Error('Unable to load data.');
      })
      .then(json => {
        if (typeof json != 'undefined' && typeof json.search_results != 'undefined') {
          this.errorMessage = '';
          this.data = json.search_results;
          this.facets = json.facets;

          if (json.pager) {
            this.pager = json.pager;
            this.hasMultiplePages = this.pager.total_pages > 1;
          }
        }
        else {
          throw new Error('No data found.');
        }

        if (this.fetchCb) {
          this.fetchCb();
        }
      })
      .catch(error => {
        this.data = [];
        this.facets = [];
        this.pager = null;
        this.errorMessage = error;
      });
  }
}


