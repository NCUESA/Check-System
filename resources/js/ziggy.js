const Ziggy = {"url":"http:\/\/localhost","port":null,"defaults":{},"routes":{"people.index":{"uri":"people","methods":["GET","HEAD"]},"people.store":{"uri":"people","methods":["POST"]}}};
if (typeof window !== 'undefined' && typeof window.Ziggy !== 'undefined') {
  Object.assign(Ziggy.routes, window.Ziggy.routes);
}
export { Ziggy };
