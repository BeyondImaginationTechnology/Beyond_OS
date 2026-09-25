package technology.co.beyondimagination.dailybreath;

import android.content.Context;
import android.graphics.Canvas;
import android.graphics.Paint;
import android.graphics.Path;
import android.view.View;

/** Small, resolution-independent icons for the main navigation. */
final class NavIconView extends View {
    private final int icon;
    private final Paint pen = new Paint(Paint.ANTI_ALIAS_FLAG);

    NavIconView(Context context, int icon, int color) {
        super(context);
        this.icon = icon;
        pen.setColor(color);
        pen.setStyle(Paint.Style.STROKE);
        pen.setStrokeWidth(1.8f);
        pen.setStrokeCap(Paint.Cap.ROUND);
        pen.setStrokeJoin(Paint.Join.ROUND);
    }

    @Override protected void onDraw(Canvas canvas) {
        super.onDraw(canvas);
        canvas.save();
        canvas.scale(getWidth() / 24f, getHeight() / 24f);
        Path path = new Path();
        switch (icon) {
            case 0: // Today: sun
                canvas.drawCircle(12, 12, 4, pen);
                for (int n = 0; n < 8; n++) {
                    double a = n * Math.PI / 4;
                    canvas.drawLine(12 + (float)Math.cos(a) * 7, 12 + (float)Math.sin(a) * 7,
                        12 + (float)Math.cos(a) * 9.5f, 12 + (float)Math.sin(a) * 9.5f, pen);
                }
                break;
            case 1: // Scripture: open book
                path.moveTo(12, 20); path.lineTo(12, 5); path.cubicTo(9, 3.5f, 5, 3.5f, 2, 5);
                path.lineTo(2, 18); path.cubicTo(5, 16.5f, 9, 17, 12, 20);
                path.cubicTo(15, 17, 19, 16.5f, 22, 18); path.lineTo(22, 5);
                path.cubicTo(19, 3.5f, 15, 3.5f, 12, 5); canvas.drawPath(path, pen);
                break;
            case 2: // Chat: overlapping bubbles
                canvas.drawRoundRect(2, 4, 17, 16, 4, 4, pen);
                path.moveTo(6, 16); path.lineTo(6, 20); path.lineTo(10, 16); canvas.drawPath(path, pen);
                path.reset(); path.moveTo(17, 8); path.lineTo(20, 8); path.quadTo(22, 8, 22, 11);
                path.lineTo(22, 18); path.lineTo(19, 18); path.lineTo(19, 21); path.lineTo(16, 18);
                canvas.drawPath(path, pen);
                break;
            case 3: // Academy: graduation cap
                path.moveTo(2, 9); path.lineTo(12, 4); path.lineTo(22, 9); path.lineTo(12, 14);
                path.close(); path.moveTo(6, 12); path.lineTo(6, 17); path.quadTo(12, 21, 18, 17);
                path.lineTo(18, 12); path.moveTo(22, 9); path.lineTo(22, 18); canvas.drawPath(path, pen);
                break;
            case 4: // Breathe: wind lines
                path.moveTo(2, 8); path.lineTo(15, 8); path.cubicTo(20, 8, 20, 3, 16, 3);
                path.cubicTo(14, 3, 13, 4, 13, 5); path.moveTo(2, 12); path.lineTo(21, 12);
                path.moveTo(2, 16); path.lineTo(15, 16); path.cubicTo(20, 16, 20, 21, 16, 21);
                path.cubicTo(14, 21, 13, 20, 13, 19); canvas.drawPath(path, pen);
                break;
            case 5: // Journal: pencil on page
                canvas.drawRoundRect(4, 2, 18, 22, 2, 2, pen);
                path.moveTo(8, 8); path.lineTo(14, 8); path.moveTo(8, 12); path.lineTo(12, 12);
                path.moveTo(11, 18); path.lineTo(20, 9); path.lineTo(22, 11); path.lineTo(13, 20);
                path.lineTo(10, 21); path.close(); canvas.drawPath(path, pen);
                break;
            default: // Home: roof and doorway
                path.moveTo(2, 11); path.lineTo(12, 3); path.lineTo(22, 11);
                path.moveTo(5, 10); path.lineTo(5, 21); path.lineTo(19, 21); path.lineTo(19, 10);
                path.moveTo(10, 21); path.lineTo(10, 14); path.lineTo(14, 14); path.lineTo(14, 21);
                canvas.drawPath(path, pen);
                break;
        }
        canvas.restore();
    }
}
