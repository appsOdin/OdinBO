(() => {
    const state = {
        canvas: null,
        ctx: null,
        drawing: false,
        lastX: 0,
        lastY: 0
    };

    const getPointerPosition = (event) => {
        if (!state.canvas) {
            return { x: 0, y: 0 };
        }

        const rect = state.canvas.getBoundingClientRect();
        const source = event.touches && event.touches[0] ? event.touches[0] : event;

        return {
            x: source.clientX - rect.left,
            y: source.clientY - rect.top
        };
    };

    const resizeCanvas = () => {
        if (!state.canvas || !state.ctx) {
            return;
        }

        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const width = Math.max(state.canvas.offsetWidth, 320);
        const height = Math.max(state.canvas.offsetHeight, 180);

        state.canvas.width = Math.floor(width * ratio);
        state.canvas.height = Math.floor(height * ratio);
        state.ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        state.ctx.clearRect(0, 0, width, height);
    };

    const startDrawing = (event) => {
        event.preventDefault();
        state.drawing = true;
        const pos = getPointerPosition(event);
        state.lastX = pos.x;
        state.lastY = pos.y;
    };

    const draw = (event) => {
        if (!state.drawing || !state.ctx) {
            return;
        }

        event.preventDefault();
        const pos = getPointerPosition(event);

        state.ctx.beginPath();
        state.ctx.moveTo(state.lastX, state.lastY);
        state.ctx.lineTo(pos.x, pos.y);
        state.ctx.stroke();

        state.lastX = pos.x;
        state.lastY = pos.y;
    };

    const stopDrawing = () => {
        state.drawing = false;
    };

    const init = (canvasId) => {
        const canvas = document.getElementById(canvasId);
        if (!(canvas instanceof HTMLCanvasElement)) {
            return;
        }

        state.canvas = canvas;
        state.ctx = canvas.getContext('2d');
        if (!state.ctx) {
            return;
        }

        state.ctx.strokeStyle = '#111827';
        state.ctx.lineWidth = 2;
        state.ctx.lineCap = 'round';
        state.ctx.lineJoin = 'round';

        resizeCanvas();

        canvas.onmousedown = startDrawing;
        canvas.onmousemove = draw;
        canvas.onmouseup = stopDrawing;
        canvas.onmouseleave = stopDrawing;

        canvas.ontouchstart = startDrawing;
        canvas.ontouchmove = draw;
        canvas.ontouchend = stopDrawing;
        canvas.ontouchcancel = stopDrawing;

        window.addEventListener('resize', resizeCanvas, { passive: true });
    };

    const clear = () => {
        if (!state.canvas || !state.ctx) {
            return;
        }

        state.ctx.clearRect(0, 0, state.canvas.width, state.canvas.height);
    };

    const toDataUrl = () => {
        if (!state.canvas) {
            return '';
        }

        return state.canvas.toDataURL('image/png');
    };

    const isEmpty = () => {
        if (!state.canvas) {
            return true;
        }

        const blankCanvas = document.createElement('canvas');
        blankCanvas.width = state.canvas.width;
        blankCanvas.height = state.canvas.height;
        return state.canvas.toDataURL('image/png') === blankCanvas.toDataURL('image/png');
    };

    window.VacationSignaturePad = {
        init,
        clear,
        toDataUrl,
        isEmpty
    };
})();
