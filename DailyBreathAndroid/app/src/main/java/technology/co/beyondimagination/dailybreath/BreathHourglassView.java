package technology.co.beyondimagination.dailybreath;

import android.content.Context;
import android.graphics.Canvas;
import android.graphics.Color;
import android.graphics.Paint;
import android.graphics.Path;
import android.view.View;

/** Hourglass that follows the current inhale, hold, and exhale cycle. */
final class BreathHourglassView extends View {
    private final int primary, accent, surface;
    private final Paint paint = new Paint(Paint.ANTI_ALIAS_FLAG);
    private int elapsed;
    private boolean running;
    private boolean complete;

    BreathHourglassView(Context context, int primary, int accent, int surface) {
        super(context);this.primary=primary;this.accent=accent;this.surface=surface;
        setContentDescription("Breathing hourglass");
    }

    void setState(int elapsed, boolean running, boolean complete) {
        this.elapsed = Math.max(0, Math.min(14, elapsed));
        this.running = running;
        this.complete = complete;
        invalidate();
    }

    @Override protected void onDraw(Canvas canvas) {
        super.onDraw(canvas);
        canvas.save();
        float size = Math.min(getWidth(), getHeight());
        canvas.translate((getWidth() - size) / 2, (getHeight() - size) / 2);
        canvas.scale(size / 240f, size / 240f);

        paint.setStyle(Paint.Style.FILL); paint.setColor(surface == Color.rgb(17, 31, 47) ? Color.rgb(30, 55, 70) : Color.rgb(245, 239, 224));
        canvas.drawCircle(120, 120, 108, paint);
        paint.setColor(surface); canvas.drawCircle(120, 120, 92, paint);

        // Glass outline and flowing sand.
        Path glass = new Path();
        glass.moveTo(69, 34); glass.lineTo(171, 34);
        glass.cubicTo(166, 75, 143, 92, 123, 117);
        glass.cubicTo(143, 142, 166, 162, 171, 206);
        glass.lineTo(69, 206); glass.cubicTo(74, 162, 97, 142, 117, 117);
        glass.cubicTo(97, 92, 74, 75, 69, 34); glass.close();
        paint.setColor(surface); canvas.drawPath(glass, paint);

        float progress = complete ? 1f : running ? elapsed / 14f : Math.max(0, elapsed / 14f);
        float top = 75 + progress * 32;
        Path upper = new Path(); upper.moveTo(84 + progress * 22, top);
        upper.lineTo(156 - progress * 22, top); upper.lineTo(120, 117); upper.close();
        paint.setColor(accent); canvas.drawPath(upper, paint);
        Path lower = new Path(); lower.moveTo(120, 121);
        lower.lineTo(160, 188); lower.lineTo(80, 188); lower.close();
        paint.setColor(accent); canvas.drawPath(lower, paint);
        paint.setColor(surface); canvas.drawRect(77, 122, 163, 184 - progress * 30, paint);
        if (running && elapsed >= 4 && elapsed < 8) {
            paint.setColor(accent); canvas.drawRect(118, 116, 122, 143, paint);
        }

        paint.setColor(primary); paint.setStyle(Paint.Style.STROKE); paint.setStrokeWidth(5);
        paint.setStrokeJoin(Paint.Join.ROUND); canvas.drawPath(glass, paint);
        paint.setStyle(Paint.Style.FILL); canvas.drawRoundRect(62, 29, 178, 38, 4, 4, paint);
        canvas.drawRoundRect(62, 202, 178, 211, 4, 4, paint);
        canvas.restore();
    }
}
