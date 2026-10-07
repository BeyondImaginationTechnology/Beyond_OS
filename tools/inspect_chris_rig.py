import bpy
fbx = r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos\imports\chris_rigged\tripo_convert_6f7facd5-efbd-4926-8914-fd78c7b4ec0f.fbx"
bpy.ops.import_scene.fbx(filepath=fbx)
print('IMPORTED')
for o in bpy.context.selected_objects:
    print(o.name, o.type, tuple(round(v,4) for v in o.dimensions), tuple(round(v,4) for v in o.location))
