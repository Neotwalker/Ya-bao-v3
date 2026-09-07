export function runInitializers(...initializers) {
  initializers.forEach(initializer => {
    try {
      const result = initializer();
      if (result && typeof result.catch === 'function') {
        result.catch(error => console.error(`${initializer.name} failed`, error));
      }
    } catch (error) {
      console.error(`${initializer.name} failed`, error);
    }
  });
}
