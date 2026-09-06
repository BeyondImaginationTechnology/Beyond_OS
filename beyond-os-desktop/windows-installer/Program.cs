using System;
using System.Collections.Generic;
using System.Drawing;
using System.IO;
using System.Management;
using System.Net;
using System.Security.Cryptography;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace BITOSInstaller
{
    internal sealed class ReleaseDefinition
    {
        public string Edition;
        public string Version;
        public string ImageUrl;
        public string ChecksumsUrl;
        public bool Available;
        public override string ToString() { return Edition + " " + Version + (Available ? "" : " (coming soon)"); }
    }

    internal sealed class DiskChoice
    {
        public int Index;
        public string Caption;
        public string DeviceId;
        public ulong Size;
        public override string ToString() { return "Disk " + Index + " — " + Caption + " — " + FormatBytes(Size); }
        private static string FormatBytes(ulong value) { return value >= 1073741824UL ? (value / 1073741824.0).ToString("0.0") + " GB" : (value / 1048576.0).ToString("0") + " MB"; }
    }

    internal sealed class InstallerForm : Form
    {
        private readonly ComboBox editions = new ComboBox();
        private readonly ComboBox disks = new ComboBox();
        private readonly TextBox imagePath = new TextBox();
        private readonly TextBox confirmation = new TextBox();
        private readonly Label status = new Label();
        private readonly Button download = new Button();
        private readonly Button choose = new Button();
        private readonly Button verify = new Button();
        private readonly Button refresh = new Button();
        private readonly Button write = new Button();
        private readonly List<ReleaseDefinition> releases = new List<ReleaseDefinition>
        {
            new ReleaseDefinition {
                Edition = "BIT OS Cyber", Version = "1.0", Available = true,
                ImageUrl = "https://beyondimagination.co.technology/downloads/cyber/1.0/bit-os-cyber-1.0-installer.img",
                ChecksumsUrl = "https://beyondimagination.co.technology/downloads/cyber/1.0/SHA256SUMS"
            },
            new ReleaseDefinition {
                Edition = "BIT OS Home", Version = "1.0", Available = false,
                ImageUrl = "https://beyondimagination.co.technology/downloads/home/1.0/bit-os-home-1.0-installer.img",
                ChecksumsUrl = "https://beyondimagination.co.technology/downloads/home/1.0/SHA256SUMS"
            }
        };
        private bool verified;

        [STAThread]
        private static void Main()
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);
            Application.Run(new InstallerForm());
        }

        public InstallerForm()
        {
            Text = "BIT OS Installer";
            StartPosition = FormStartPosition.CenterScreen;
            MinimumSize = new Size(720, 600);
            Size = new Size(790, 660);
            BackColor = Color.FromArgb(11, 17, 27);
            ForeColor = Color.White;
            Font = new Font("Segoe UI", 10F);

            var title = new Label { Text = "BIT OS Installer", Font = new Font("Segoe UI Semibold", 24F), AutoSize = true, Location = new Point(32, 25) };
            var subtitle = new Label { Text = "Verified USB media for BIT OS. Installation and partitioning finish after restart.", ForeColor = Color.FromArgb(184, 203, 219), AutoSize = true, Location = new Point(34, 67) };
            Controls.Add(title); Controls.Add(subtitle);

            var grid = new TableLayoutPanel { Location = new Point(32, 110), Size = new Size(710, 345), ColumnCount = 2, RowCount = 6, BackColor = BackColor };
            grid.ColumnStyles.Add(new ColumnStyle(SizeType.Absolute, 175)); grid.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100));
            for (var i = 0; i < 6; i++) grid.RowStyles.Add(new RowStyle(SizeType.Absolute, i == 2 ? 68 : 48));
            AddRow(grid, 0, "Edition", editions); AddRow(grid, 1, "Installer image", imagePath); AddRow(grid, 3, "USB drive", disks); AddRow(grid, 4, "Confirm erase", confirmation);
            imagePath.ReadOnly = true; imagePath.BackColor = Color.FromArgb(27, 40, 55); imagePath.ForeColor = Color.White; imagePath.BorderStyle = BorderStyle.FixedSingle;
            editions.DropDownStyle = ComboBoxStyle.DropDownList; disks.DropDownStyle = ComboBoxStyle.DropDownList;
            StyleCombo(editions); StyleCombo(disks); StyleText(confirmation);
            Controls.Add(grid);

            download.Text = "Download and verify"; choose.Text = "Use local image"; verify.Text = "Verify selected image"; refresh.Text = "Refresh USB drives"; write.Text = "Write USB installer";
            var buttons = new[] { download, choose, verify, refresh, write };
            for (var i = 0; i < buttons.Length; i++) { StyleButton(buttons[i], i == 4 ? Color.FromArgb(183, 60, 64) : Color.FromArgb(29, 104, 145)); }
            download.Location = new Point(32, 470); choose.Location = new Point(198, 470); verify.Location = new Point(360, 470); refresh.Location = new Point(32, 518); write.Location = new Point(198, 518);
            Controls.AddRange(buttons);

            status.Location = new Point(32, 575); status.Size = new Size(710, 40); status.ForeColor = Color.FromArgb(171, 223, 194); status.Text = "Choose an edition and download or select a local installer image."; Controls.Add(status);
            var warning = new Label { Text = "Writing erases the selected USB drive. Confirm the exact physical-disk number shown above; internal disks are not listed.", Location = new Point(32, 545), Size = new Size(710, 22), ForeColor = Color.FromArgb(242, 190, 108) }; Controls.Add(warning);

            editions.DataSource = releases;
            editions.SelectedIndexChanged += delegate { verified = false; imagePath.Text = ""; var r = CurrentRelease(); status.Text = r.Available ? "Cyber is available now. Home will activate when its signed release is published." : "This edition is not published yet."; };
            download.Click += async delegate { await DownloadAndVerifyAsync(); };
            choose.Click += delegate { ChooseImage(); };
            verify.Click += async delegate { await VerifyAsync(); };
            refresh.Click += delegate { RefreshDisks(); };
            write.Click += async delegate { await WriteUsbAsync(); };
            RefreshDisks();
        }

        private void AddRow(TableLayoutPanel grid, int row, string label, Control control)
        {
            var text = new Label { Text = label, Dock = DockStyle.Fill, TextAlign = ContentAlignment.MiddleLeft, ForeColor = Color.FromArgb(190, 209, 224) };
            control.Dock = DockStyle.Fill; grid.Controls.Add(text, 0, row); grid.Controls.Add(control, 1, row);
        }
        private void StyleCombo(ComboBox c) { c.BackColor = Color.FromArgb(27, 40, 55); c.ForeColor = Color.White; c.FlatStyle = FlatStyle.Flat; }
        private void StyleText(TextBox t) { t.BackColor = Color.FromArgb(27, 40, 55); t.ForeColor = Color.White; t.BorderStyle = BorderStyle.FixedSingle; }
        private void StyleButton(Button b, Color color) { b.Size = new Size(152, 36); b.FlatStyle = FlatStyle.Flat; b.FlatAppearance.BorderColor = color; b.BackColor = color; b.ForeColor = Color.White; b.Font = new Font("Segoe UI Semibold", 9F); }
        private ReleaseDefinition CurrentRelease() { return (ReleaseDefinition)editions.SelectedItem; }

        private async Task DownloadAndVerifyAsync()
        {
            var release = CurrentRelease();
            if (!release.Available) { status.Text = release.Edition + " is not published yet."; return; }
            using (var dialog = new SaveFileDialog { FileName = Path.GetFileName(new Uri(release.ImageUrl).LocalPath), Filter = "BIT OS image (*.img)|*.img" })
            {
                if (dialog.ShowDialog(this) != DialogResult.OK) return;
                Toggle(false); status.Text = "Downloading " + release.Edition + " installer image…";
                try { using (var client = new WebClient()) { await client.DownloadFileTaskAsync(new Uri(release.ImageUrl), dialog.FileName); } imagePath.Text = dialog.FileName; await VerifyAsync(); }
                catch (Exception ex) { status.Text = "Download failed: " + ex.Message; }
                finally { Toggle(true); }
            }
        }

        private void ChooseImage()
        {
            using (var dialog = new OpenFileDialog { Filter = "BIT OS images (*.img)|*.img|All files (*.*)|*.*" })
            { if (dialog.ShowDialog(this) == DialogResult.OK) { imagePath.Text = dialog.FileName; verified = false; status.Text = "Local image selected. Verify it before writing."; } }
        }

        private async Task VerifyAsync()
        {
            var release = CurrentRelease();
            if (String.IsNullOrWhiteSpace(imagePath.Text) || !File.Exists(imagePath.Text)) { status.Text = "Select a local image first."; return; }
            Toggle(false); status.Text = "Verifying SHA-256…"; verified = false;
            try
            {
                string manifest; using (var client = new WebClient()) manifest = await client.DownloadStringTaskAsync(new Uri(release.ChecksumsUrl));
                var filename = Path.GetFileName(imagePath.Text); string expected = null;
                foreach (var line in manifest.Replace("\r", "").Split('\n')) if (line.EndsWith(filename, StringComparison.Ordinal)) { var fields = line.Trim().Split(new[] { ' ' }, StringSplitOptions.RemoveEmptyEntries); if (fields.Length > 0) expected = fields[0]; }
                if (String.IsNullOrWhiteSpace(expected)) throw new InvalidOperationException("No checksum was found for " + filename + ".");
                string actual; using (var sha = SHA256.Create()) using (var stream = File.OpenRead(imagePath.Text)) actual = BitConverter.ToString(sha.ComputeHash(stream)).Replace("-", "").ToLowerInvariant();
                if (!String.Equals(actual, expected, StringComparison.OrdinalIgnoreCase)) throw new InvalidOperationException("Checksum does not match the published release.");
                verified = true; status.Text = "Verified. Select the removable USB drive, then type ERASE <disk number>.";
            }
            catch (Exception ex) { status.Text = "Verification failed: " + ex.Message; }
            finally { Toggle(true); }
        }

        private void RefreshDisks()
        {
            disks.Items.Clear();
            try
            {
                using (var search = new ManagementObjectSearcher("SELECT Index, Model, DeviceID, Size, InterfaceType FROM Win32_DiskDrive WHERE InterfaceType='USB'"))
                foreach (ManagementObject item in search.Get())
                {
                    ulong size = 0; UInt64.TryParse(Convert.ToString(item["Size"]), out size);
                    disks.Items.Add(new DiskChoice { Index = Convert.ToInt32(item["Index"]), Caption = Convert.ToString(item["Model"]), DeviceId = Convert.ToString(item["DeviceID"]), Size = size });
                }
                if (disks.Items.Count > 0) disks.SelectedIndex = 0;
                status.Text = disks.Items.Count > 0 ? "Select the USB drive by its physical disk number." : "No USB disks detected. Connect a removable USB drive and refresh.";
            }
            catch (Exception ex) { status.Text = "Could not list USB disks: " + ex.Message; }
        }

        private async Task WriteUsbAsync()
        {
            var disk = disks.SelectedItem as DiskChoice;
            if (!verified) { status.Text = "Verify the image before writing."; return; }
            if (disk == null) { status.Text = "Select a USB drive first."; return; }
            if (!String.Equals(confirmation.Text.Trim(), "ERASE " + disk.Index, StringComparison.Ordinal)) { status.Text = "Type ERASE " + disk.Index + " to confirm the selected USB drive."; return; }
            var answer = MessageBox.Show(this, "This permanently erases " + disk + ".\n\nWrite the verified BIT OS image?", "Confirm USB erase", MessageBoxButtons.YesNo, MessageBoxIcon.Warning, MessageBoxDefaultButton.Button2);
            if (answer != DialogResult.Yes) return;
            Toggle(false); status.Text = "Writing image to USB. Do not remove the drive…";
            try { await Task.Run(() => CopyImageToDisk(imagePath.Text, disk.DeviceId)); status.Text = "USB installer created. Restart the computer and boot this USB drive to install BIT OS."; }
            catch (Exception ex) { status.Text = "USB write failed: " + ex.Message; }
            finally { Toggle(true); }
        }

        private static void CopyImageToDisk(string sourcePath, string devicePath)
        {
            const int bufferSize = 4 * 1024 * 1024;
            var buffer = new byte[bufferSize];
            using (var source = new FileStream(sourcePath, FileMode.Open, FileAccess.Read, FileShare.Read))
            using (var target = new FileStream(devicePath, FileMode.Open, FileAccess.ReadWrite, FileShare.ReadWrite))
            {
                int read; while ((read = source.Read(buffer, 0, buffer.Length)) > 0) target.Write(buffer, 0, read);
                target.Flush(true);
            }
        }

        private void Toggle(bool enabled) { download.Enabled = choose.Enabled = verify.Enabled = refresh.Enabled = write.Enabled = editions.Enabled = disks.Enabled = enabled; }
    }
}