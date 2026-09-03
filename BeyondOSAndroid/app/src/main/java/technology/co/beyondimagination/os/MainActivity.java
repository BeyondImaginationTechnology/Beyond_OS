package technology.co.beyondimagination.os;

import android.app.Activity;
import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.view.View;
import android.widget.TextView;

public final class MainActivity extends Activity {
    private static final String OS_URL = "https://os.beyondimagination.co.technology/";

    @Override public void onCreate(Bundle state) {
        super.onCreate(state);
        setContentView(R.layout.activity_main);
        findViewById(R.id.open_os).setOnClickListener(view -> open(OS_URL));
        findViewById(R.id.open_studio).setOnClickListener(view -> open("https://os.beyondimagination.co.technology/studio"));
        TextView status = findViewById(R.id.status);
        status.setText(getString(R.string.status_ready));
    }

    private void open(String url) { startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse(url))); }
}
