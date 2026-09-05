/* Native storage and file-view tests; run only against a disposable fixture. */
#define main beyond_desktop_main
#include "../src/home.c"
#undef main
#include <assert.h>

int main(int argc, char **argv)
{
    assert(argc == 2);
    assert(join_path(note_path, sizeof note_path, argv[1], "Home Note.txt"));
    char tiny[4];
    assert(!join_path(tiny, sizeof tiny, "/too-long", "name"));
    snprintf(note, sizeof note, "First note\n");
    dirty = true;
    assert(save_note() && !dirty);
    snprintf(note, sizeof note, "Second note: caf\xc3\xa9\n");
    dirty = true;
    assert(save_note() && !dirty);
    FILE *file = fopen(note_path, "rb");
    assert(file);
    char readback[128] = {0};
    assert(fread(readback, 1, sizeof readback - 1, file) == strlen(note));
    fclose(file);
    assert(!strcmp(readback, note));

    char saved_path[PATH_CAP];
    snprintf(saved_path, sizeof saved_path, "%s", note_path);
    assert(join_path(note_path, sizeof note_path, argv[1], "missing/fails.txt"));
    dirty = true;
    assert(!save_note() && dirty);
    assert(!strcmp(note, readback));
    snprintf(note_path, sizeof note_path, "%s", saved_path);

    char binary_path[PATH_CAP];
    assert(join_path(binary_path, sizeof binary_path, argv[1], "binary.dat"));
    file = fopen(binary_path, "wb");
    assert(file);
    assert(fwrite("A\0B", 1, 3, file) == 3);
    fclose(file);
    snprintf(directory, sizeof directory, "%s", argv[1]);
    page = FILES;
    load_directory();
    int note_index = -1, binary_index = -1, folder_index = -1;
    for (int i = 0; i < entry_count; i++) {
        if (!strcmp(entries[i].name, "Home Note.txt")) note_index = i;
        if (!strcmp(entries[i].name, "binary.dat")) binary_index = i;
        if (!strcmp(entries[i].name, "empty-folder")) folder_index = i;
    }
    assert(note_index >= 0 && binary_index >= 0 && folder_index >= 0);
    open_entry(note_index);
    assert(page == VIEWER && !strcmp(file_text, readback));
    page = FILES;
    open_entry(binary_index);
    assert(page == FILES && strstr(status, "plain text"));
    open_entry(folder_index);
    assert(page == FILES && entry_count == 0);
    puts("PASS: note creation/replacement, failed-save retention, path bounds, UTF-8 preview, binary rejection, empty folder");
    return 0;
}
