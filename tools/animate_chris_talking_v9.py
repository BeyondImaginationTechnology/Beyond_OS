import bpy
from math import radians
out=r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos\DB_Christian_StudyV9_Chris_Talking.blend"
scene=bpy.context.scene
scene.render.fps=24
scene.frame_start=1
scene.frame_end=144
arm=bpy.data.objects['Chris_Rig']
# Fresh reusable action for narration loops.
if arm.animation_data: arm.animation_data_clear()
act=bpy.data.actions.get('Chris_Talking_6s') or bpy.data.actions.new('Chris_Talking_6s')
arm.animation_data_create()
arm.animation_data.action=act
base={}
for n in ['mixamorig:Hips','mixamorig:Spine','mixamorig:Spine1','mixamorig:Spine2','mixamorig:Neck','mixamorig:Head','mixamorig:LeftArm','mixamorig:RightArm','mixamorig:LeftForeArm','mixamorig:RightForeArm','mixamorig:LeftHand','mixamorig:RightHand','mixamorig:LeftUpLeg','mixamorig:RightUpLeg','mixamorig:LeftLeg','mixamorig:RightLeg','mixamorig:LeftFoot','mixamorig:RightFoot']:
    b=arm.pose.bones.get(n)
    if b:
        b.rotation_mode='XYZ'
        base[n]=b.rotation_euler.copy()
# Natural beats: torso and head lead; hands gesture above desk while legs remain seated.
beats={
 1: {'Hips':(0,0,0),'Spine':(0,0,0),'Spine1':(0,0,0),'Spine2':(0,0,0),'Neck':(0,0,0),'Head':(0,0,0),'LeftArm':(0,0,0),'RightArm':(0,0,0),'LeftForeArm':(0,0,0),'RightForeArm':(0,0,0)},
 18:{'Hips':(1,0,0),'Spine':(-1,0,1),'Spine1':(-1,0,1),'Spine2':(-1,0,1),'Neck':(2,0,-2),'Head':(3,0,-2),'LeftArm':(-4,0,13),'RightArm':(-5,0,-7),'LeftForeArm':(5,0,9),'RightForeArm':(2,0,-8)},
 42:{'Hips':(0,0,0),'Spine':(-2,0,-1),'Spine1':(-2,0,-1),'Spine2':(-1,0,-1),'Neck':(-1,0,2),'Head':(-3,0,2),'LeftArm':(-6,0,-8),'RightArm':(-5,0,15),'LeftForeArm':(5,0,-10),'RightForeArm':(7,0,10)},
 66:{'Hips':(1,0,0),'Spine':(-1,0,1),'Spine1':(-1,0,1),'Spine2':(-1,0,1),'Neck':(2,0,-1),'Head':(2,0,-1),'LeftArm':(-4,0,10),'RightArm':(-4,0,10),'LeftForeArm':(2,0,8),'RightForeArm':(2,0,8)},
 90:{'Hips':(0,0,0),'Spine':(-2,0,0),'Spine1':(-1,0,0),'Spine2':(-1,0,0),'Neck':(-2,0,1),'Head':(-2,0,1),'LeftArm':(-4,0,-9),'RightArm':(-4,0,-9),'LeftForeArm':(4,0,-8),'RightForeArm':(4,0,-8)},
 114:{'Hips':(1,0,0),'Spine':(-1,0,-1),'Spine1':(-1,0,-1),'Spine2':(-1,0,-1),'Neck':(2,0,2),'Head':(3,0,2),'LeftArm':(-6,0,8),'RightArm':(-5,0,-12),'LeftForeArm':(7,0,10),'RightForeArm':(4,0,-12)},
 144:{'Hips':(0,0,0),'Spine':(0,0,0),'Spine1':(0,0,0),'Spine2':(0,0,0),'Neck':(0,0,0),'Head':(0,0,0),'LeftArm':(0,0,0),'RightArm':(0,0,0),'LeftForeArm':(0,0,0),'RightForeArm':(0,0,0)},
}
for frame, vals in beats.items():
    scene.frame_set(frame)
    for key, delta in vals.items():
        n='mixamorig:'+key
        b=arm.pose.bones.get(n)
        if b and n in base:
            b.rotation_euler=base[n].copy()
            b.rotation_euler.x += radians(delta[0])
            b.rotation_euler.y += radians(delta[1])
            b.rotation_euler.z += radians(delta[2])
            b.keyframe_insert(data_path='rotation_euler',frame=frame)
    # lock leg pose from seated setup.
    for n in ['mixamorig:LeftUpLeg','mixamorig:RightUpLeg','mixamorig:LeftLeg','mixamorig:RightLeg','mixamorig:LeftFoot','mixamorig:RightFoot']:
        b=arm.pose.bones.get(n)
        if b and n in base:
            b.rotation_euler=base[n].copy(); b.keyframe_insert(data_path='rotation_euler',frame=frame)
scene.frame_set(1)
scene['chris_narration_loop']='Chris_Talking_6s'
scene['voice_target']='ElevenLabs Ryan'
scene['facial_animation']='None: rig has no mouth bones or shape keys'
bpy.ops.wm.save_as_mainfile(filepath=out)
print('SAVED',out)
print('ACTION_FRAMES',scene.frame_start,scene.frame_end)

