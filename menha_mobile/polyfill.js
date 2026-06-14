const { ReadableStream, WritableStream, TransformStream } = require('stream/web');
const os = require('os');

// Polyfill Web Streams
if (typeof global.ReadableStream === 'undefined') {
  global.ReadableStream = ReadableStream;
}
if (typeof global.WritableStream === 'undefined') {
  global.WritableStream = WritableStream;
}
if (typeof global.TransformStream === 'undefined') {
  global.TransformStream = TransformStream;
}

// Polyfill os.availableParallelism (introduced in Node 18.14+)
if (typeof os.availableParallelism !== 'function') {
  os.availableParallelism = function() {
    return os.cpus().length || 1;
  };
}

// Polyfill Array.prototype.toReversed (introduced in Node 20+)
if (typeof Array.prototype.toReversed !== 'function') {
  Array.prototype.toReversed = function() {
    return [...this].reverse();
  };
}

// Polyfill AbortSignal.prototype.throwIfAborted (introduced in Node 17.3+)
if (typeof global.AbortSignal !== 'undefined' && typeof global.AbortSignal.prototype.throwIfAborted !== 'function') {
  global.AbortSignal.prototype.throwIfAborted = function() {
    if (this.aborted) {
      const err = new Error('The operation was aborted.');
      err.name = 'AbortError';
      throw err;
    }
  };
}

// Polyfill URL.canParse (introduced in Node 19.9+)
if (typeof URL.canParse !== 'function') {
  URL.canParse = function(url, base) {
    try {
      new URL(url, base);
      return true;
    } catch (e) {
      return false;
    }
  };
}
