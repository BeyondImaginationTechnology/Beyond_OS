import bpy
s=bpy.context.scene
s.render.resolution_x=1280;s.render.resolution_y=720;s.render.resolution_percentage=100;s.render.image_settings.file_format='PNG';s.render.filepath=r'C:\Users\Greg\AppData\Local\Temp\chris-forest-v16-close.png'
s.camera=bpy.data.objects['Chris_Morning_Close_Camera'];s.frame_set(110);bpy.ops.render.render(write_still=True)
