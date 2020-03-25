// Import the LitElement base class and html helper function
import { LitElement, html } from 'lit-element';

export class OchaAssessmentsBase extends LitElement {
  constructor() {
    super();
    this.data = void 0;
    this.facets = void 0;
    this.pager = void 0;
    this.hasMultiplePages = false;
    this.resetUrl = null;
  }

  static get properties() {
    return {
      src: {
        type: String
      },
      data: {
        type: Array
      }
    };
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
    this.src = event.currentTarget.options[event.currentTarget.selectedIndex].value;
  }

  buildFacets() {
    if (!this.facets) {
      return [];
    }

    let dropdowns = [];

    this.facets.forEach(function (child) {
      if (child.length > 0) {
        if (typeof child[0][0] == 'undefined') {
          let dropdown = {};
          for (const id in child[0]) {
            dropdown = {
              label: id,
              selected: null,
              selected_url: null,
              options: []
            };

            child[0][id].forEach(function (option) {
              if (typeof option.values.active != 'undefined') {
                dropdown.selected = option.values.value;
                dropdown.selected_url = option.url;
              }

              dropdown.options.push({
                key: option.url,
                label: option.values.value
              });
            });
          }

          dropdowns.push(dropdown);
        }
      }
    });

    return dropdowns;
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
      <label for="${dropdown.label}">${dropdown.label}</label>
      <select @change="${this.changeSrc}" id="${dropdown.label}">
        <option value="${emptytOption.value}" ?selected=${dropdown.selected === null}>${emptytOption.label}</option>
        ${
          dropdown.options.map(function (o) {
            if (o.label == dropdown.selected) {
              return html`
                <option value="" selected>${o.label}</option>
              `
            }
            else {
              return html`
                <option value="${o.key}">${o.label}</option>
              `
            }
          })
        }
      </select>
    `;
  }

  renderDate(data) {
    let output = data.field_ass_date;

    if (typeof data.field_ass_date_end_value != 'undefined' && data.field_ass_date_end_value.length > 0) {
      output = output + ' - ' + data.field_ass_date_end_value[0];
    }

    return output;
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
    }
    else {
      console.error('src attribute is required.')
    }
  }

  fetchData() {
    fetch(this.src)
      .then(res => res.json())
      .then(response => {
        this.data = response.search_results;
        this.facets = response.facets;

        if (response.pager) {
          this.pager = response.pager;
          this.hasMultiplePages = this.pager.total_pages > 1;
        }

        if (this.fetchCb) {
          this.fetchCb();
        }
      })
      .catch(error => console.error("Error fetching data:", error));
  }
}


