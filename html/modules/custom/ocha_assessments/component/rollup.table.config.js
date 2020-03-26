import resolve from 'rollup-plugin-node-resolve';

export default {
  input: ['src/ocha-assessments-table.js'],
  output: {
    file: 'build/ocha-assessments-table.js',
    format: 'es',
    sourcemap: true
  },
  plugins: [
    resolve()
  ]
};
