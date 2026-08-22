// Sentinel OS / VisionGuard Digital Signature Canvas Helper

class SignaturePad {
  constructor(canvasId, clearBtnId) {
    this.canvas = document.getElementById(canvasId);
    if (!this.canvas) return;
    this.ctx = this.canvas.getContext('2d');
    this.clearBtn = document.getElementById(clearBtnId);
    this.isDrawing = false;

    this.setupCanvas();
    this.bindEvents();
  }

  setupCanvas() {
    this.ctx.strokeStyle = '#000000';
    this.ctx.lineWidth = 2.5;
    this.ctx.lineCap = 'round';
    this.ctx.lineJoin = 'round';
  }

  bindEvents() {
    const getPos = (e) => {
      const rect = this.canvas.getBoundingClientRect();
      const clientX = e.touches ? e.touches[0].clientX : e.clientX;
      const clientY = e.touches ? e.touches[0].clientY : e.clientY;
      return {
        x: clientX - rect.left,
        y: clientY - rect.top
      };
    };

    const startDrawing = (e) => {
      e.preventDefault();
      this.isDrawing = true;
      const pos = getPos(e);
      this.ctx.beginPath();
      this.ctx.moveTo(pos.x, pos.y);
    };

    const draw = (e) => {
      if (!this.isDrawing) return;
      e.preventDefault();
      const pos = getPos(e);
      this.ctx.lineTo(pos.x, pos.y);
      this.ctx.stroke();
    };

    const stopDrawing = () => {
      this.isDrawing = false;
    };

    this.canvas.addEventListener('mousedown', startDrawing);
    this.canvas.addEventListener('mousemove', draw);
    this.canvas.addEventListener('mouseup', stopDrawing);
    this.canvas.addEventListener('mouseleave', stopDrawing);

    this.canvas.addEventListener('touchstart', startDrawing, { passive: false });
    this.canvas.addEventListener('touchmove', draw, { passive: false });
    this.canvas.addEventListener('touchend', stopDrawing);

    if (this.clearBtn) {
      this.clearBtn.addEventListener('click', () => this.clear());
    }
  }

  clear() {
    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
  }

  toDataURL() {
    return this.canvas.toDataURL('image/png');
  }

  isEmpty() {
    const pixelBuffer = new Uint32Array(
      this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height).data.buffer
    );
    return !pixelBuffer.some(color => color !== 0);
  }
}
