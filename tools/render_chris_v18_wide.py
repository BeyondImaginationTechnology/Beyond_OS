import bpy
s=bpy.context.scene;s.render.image_settings.file_format='PNG';s.render.filepath=r'C:\\Users\\Greg\\AppData\\Local\\Temp\\chris-forest-v18-wide.png';s.camera=bpy.data.objects['Chris_Morning_Wide_Camera'];s.frame_set(1);bpy.ops.render.render(write_still=True)
